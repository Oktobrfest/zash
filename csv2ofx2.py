import pandas as pd
from datetime import datetime
import pytz
from ofxtools.models import OFX, SIGNONMSGSRSV1, SONRS, BANKMSGSRSV1, STMTTRNRS, STMTRS
from ofxtools.models import BANKACCTFROM, STMTTRN, BANKTRANLIST, LEDGERBAL
from ofxtools.models.common import STATUS
from ofxtools.header import make_header
from ofxtools.utils import indent

def convert_csv_to_ofx(csv_file, ofx_file, bank_id="123456789", account_id="987654321", account_type="CHECKING"):
    # Load CSV with more robust parsing
    df = pd.read_csv(csv_file, dtype={
        'Date': str,
        'Description': str,
        'Original Description': str,
        'Category': str,
        'Amount': str,
        'Status': str
    })
    
    # Clean up the Amount column
    df['Amount'] = df['Amount'].str.replace(',', '').astype(float)
    
    # Convert dates to timezone-aware datetime objects
    df['Date'] = pd.to_datetime(df['Date']).dt.tz_localize('UTC')
    
    # Create OFX Header
    header = str(make_header(version=220))

    # Create SONRS with timezone-aware datetime
    current_time = datetime.now(pytz.UTC)
    
    sonrs = SONRS(
        status=STATUS(code=0, severity="INFO"),
        dtserver=current_time,
        language="ENG"
    )
    
    # Create SIGNONMSGSRSV1 with sonrs as named argument
    signonmsgsrsv1 = SIGNONMSGSRSV1(sonrs=sonrs)

    # Bank Account Information
    bankacctfrom = BANKACCTFROM(
        bankid=bank_id,
        acctid=account_id,
        accttype=account_type
    )

    # Transaction List
    transactions = []
    for _, row in df.iterrows():
        # Create unique FITID
        fitid = str(abs(hash(f"{row['Date'].strftime('%Y%m%d')}_{row['Description']}")))[:36]
        
        trn = STMTTRN(
            trntype="DEBIT" if row["Amount"] < 0 else "CREDIT",
            dtposted=row["Date"],
            trnamt=float(row["Amount"]),
            fitid=fitid,
            name=row["Description"][:32],
            memo=row["Original Description"][:255] if pd.notna(row.get("Original Description")) else ""
        )
        transactions.append(trn)

    # Calculate ledger balance (sum of all transactions)
    ledger_balance = df["Amount"].sum()

    # Create LEDGERBAL object with the current balance
    ledgerbal = LEDGERBAL(
        balamt=ledger_balance,
        dtasof=current_time
    )

    # Create BANKTRANLIST with transactions as positional arguments
    banktranlist = BANKTRANLIST(
        dtstart=min(df["Date"]),
        dtend=max(df["Date"]),
        *transactions
    )

    # Bank Statement Response with required LEDGERBAL
    stmtrs = STMTRS(
        curdef="USD",
        bankacctfrom=bankacctfrom,
        banktranlist=banktranlist,
        ledgerbal=ledgerbal
    )

    stmttrnrs = STMTTRNRS(
        trnuid="1001",
        status=STATUS(code=0, severity="INFO"),
        stmtrs=stmtrs
    )

    # Create BANKMSGSRSV1 with STMTTRNRS as a positional argument
    bankmsgsrsv1 = BANKMSGSRSV1(stmttrnrs)

    # Generate OFX Content with proper named arguments
    ofx = OFX(signonmsgsrsv1=signonmsgsrsv1, bankmsgsrsv1=bankmsgsrsv1)

    # Write to OFX File
    with open(ofx_file, "w", encoding="utf-8") as f:
        f.write(header)
        f.write("\n")
        f.write(ofx.to_xml(pretty_print=True))

    print(f"Conversion successful! OFX file saved as: {ofx_file}")

if __name__ == "__main__":
    csv_file = "transactions.csv"
    ofx_file = "transactions.ofx"
    convert_csv_to_ofx(csv_file, ofx_file)