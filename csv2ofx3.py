import pandas as pd
from datetime import datetime

# Import OFX classes and the STATUS class from ofxtools.models.common
from ofxtools.models import OFX, SIGNONMSGSRSV1, SONRS, BANKMSGSRSV1, STMTTRNRS, STMTRS, BANKACCTFROM, STMTTRN, BANKTRANLIST
from ofxtools.models.common import STATUS
from ofxtools.header import make_header
from ofxtools.utils import UTC

def format_datetime(dt):
    """Format datetime object to OFX required format."""
    if pd.isnull(dt):
        raise ValueError("Invalid datetime value")
    # Ensure datetime is timezone-aware
    if dt.tzinfo is None:
        dt = dt.replace(tzinfo=UTC)
    return dt

def convert_csv_to_ofx(csv_file, ofx_file, bank_id="123456789", account_id="987654321", account_type="CHECKING"):
    # Load CSV and ensure the Date column is parsed as datetime
    df = pd.read_csv(csv_file)
    df.columns = df.columns.str.strip()  # Strip whitespace from column names
    
    # Convert Date column to datetime
    df["Date"] = pd.to_datetime(df["Date"])
    df["Amount"] = df["Amount"].astype(float)
    
    # Debug: print head and min/max of Date column
    print("DataFrame Head:")
    print(df.head())
    
    # Get min and max dates and format them properly
    min_date = format_datetime(df["Date"].min())
    max_date = format_datetime(df["Date"].max())
    print("Min Date:", min_date, "Max Date:", max_date)
    
    # Create the OFX header
    header = make_header(version=220)

    # Create SONRS
    current_time = format_datetime(datetime.now())
    sonrs = SONRS(
        status=STATUS(code=0, severity="INFO"),
        dtserver=current_time,
        language="ENG"
    )
    signonmsgsrsv1 = SIGNONMSGSRSV1(sonrs=sonrs)

    # Create bank account information
    bankacctfrom = BANKACCTFROM(
        bankid=bank_id,
        acctid=account_id,
        accttype=account_type
    )

    # Create transactions
    transactions = []
    for _, row in df.iterrows():
        try:
            post_date = format_datetime(row["Date"])
            trn = STMTTRN(
                trntype="DEBIT" if row["Amount"] < 0 else "CREDIT",
                dtposted=post_date,
                trnamt=row["Amount"],
                fitid=str(abs(hash(str(post_date) + row["Description"]))),
                name=row["Description"][:32],
                memo=row["Original Description"] if "Original Description" in df.columns else ""
            )
            transactions.append(trn)
        except Exception as e:
            print(f"Warning: Skipping transaction due to error: {e}")
            print(f"Problematic row: {row}")
            continue

    # Create bank transaction list with properly formatted dates
    banktranlist = BANKTRANLIST(
        dtstart=min_date,
        dtend=max_date,
        stmttrn=transactions  # Changed from 'transactions' to 'stmttrn'
    )

    # Create statement response
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

    # Create final OFX object
    ofx_obj = OFX(
        signonmsgsrsv1=signonmsgsrsv1,
        bankmsgsrsv1=bankmsgsrsv1
    )

    # Write the OFX file
    with open(ofx_file, "w", encoding="utf-8") as f:
        f.write(header)
        f.write(ofx_obj.to_xml(pretty_print=True))

    print(f"Conversion successful! OFX file saved as: {ofx_file}")

if __name__ == "__main__":
    csv_file = "transactions.csv"
    ofx_file = "transactions.ofx"
    convert_csv_to_ofx(csv_file, ofx_file)