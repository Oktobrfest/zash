import pandas as pd
from datetime import datetime

# Import OFX classes and the STATUS class from ofxtools.models.common
from ofxtools.models import OFX, SIGNONMSGSRSV1, SONRS, BANKMSGSRSV1, STMTTRNRS, STMTRS, BANKACCTFROM, STMTTRN, BANKTRANLIST
from ofxtools.models.common import STATUS  # import STATUS to create proper status objects
from ofxtools.header import make_header

def convert_csv_to_ofx(csv_file, ofx_file, bank_id="123456789", account_id="987654321", account_type="CHECKING"):
    # Load CSV and ensure the Date column is parsed as datetime
    df = pd.read_csv(csv_file, parse_dates=["Date"])
    df.columns = df.columns.str.strip()  # Strip whitespace from column names
    df["Amount"] = df["Amount"].astype(float)
    
    # Debug: print head and min/max of Date column
    print("DataFrame Head:")
    print(df.head())
    # Convert pandas Timestamps to Python datetime objects
    min_date = df["Date"].min().to_pydatetime()
    max_date = df["Date"].max().to_pydatetime()
    print("Min Date:", min_date, "Max Date:", max_date)
    
    if pd.isnull(min_date) or pd.isnull(max_date):
        raise ValueError("The 'Date' column is not being parsed correctly. Please check your CSV file formatting.")

    # Create the OFX header (OFX version 2.2)
    header = make_header(version=220)

    # --- SONRS Section ---
    sonrs = SONRS(
        status=STATUS(code=0, severity="INFO"),
        dtserver=datetime.now().strftime("%Y%m%d%H%M%S"),
        language="ENG"
    )
    signonmsgsrsv1 = SIGNONMSGSRSV1(sonrs=sonrs)

    # --- Bank Account Information ---
    bankacctfrom = BANKACCTFROM(
        bankid=bank_id,
        acctid=account_id,
        accttype=account_type
    )

    # --- Transaction List ---
    transactions = []
    for _, row in df.iterrows():
        trn = STMTTRN(
            trntype="DEBIT" if row["Amount"] < 0 else "CREDIT",
            dtposted=row["Date"].strftime("%Y%m%d%H%M%S"),
            trnamt=row["Amount"],
            fitid=str(abs(hash(row["Date"].strftime("%Y%m%d") + row["Description"]))),
            name=row["Description"][:32],
            memo=row["Original Description"] if "Original Description" in df.columns else ""
        )
        transactions.append(trn)

    # --- Bank Transaction List ---
    # Instead of passing dtstart and dtend via the constructor, create an empty object and assign.
    banktranlist = BANKTRANLIST()
    banktranlist.dtstart = min_date    # assign dtstart as a datetime.datetime object
    banktranlist.dtend = max_date        # assign dtend as a datetime.datetime object
    banktranlist.stmttrn = transactions  # assign the list of transactions

    # --- Statement Response ---
    stmtrs = STMTRS(
        curdef="USD",
        bankacctfrom=bankacctfrom,
        banktranlist=banktranlist
    )

    stmttrnrs = STMTTRNRS(
        trnuid="1001",
        status=STATUS(code=0, severity="INFO"),
        stmtrs=stmtrs
    )

    bankmsgsrsv1 = BANKMSGSRSV1(stmttrnrs=stmttrnrs)

    # --- Assemble the OFX Document ---
    ofx_obj = OFX(
        signonmsgsrsv1=signonmsgsrsv1,
        bankmsgsrsv1=bankmsgsrsv1
    )

    # Write the OFX file
    with open(ofx_file, "w", encoding="utf-8") as f:
        f.write(header)
        f.write(ofx_obj.to_xml(pretty_print=True))

    print(f"Conversion successful! OFX file saved as: {ofx_file}")

# Example usage:
csv_file = "transactions.csv"  # Replace with the path to your CSV file
ofx_file = "transactions.ofx"    # Desired output OFX file name
convert_csv_to_ofx(csv_file, ofx_file)
