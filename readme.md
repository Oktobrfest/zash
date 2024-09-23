# Zash Double-Entry Bookkeeping Project

## Overview

Zash is an extension of Webzash Double Entry Bookkeeping software. It dockerizes it and allows the bulk upload of bank transactions in .OXF format as well as a series of rules to help categorize transactions.

## Features

- Bulk transaction upload
- Bank Ledger Keyword Rules
- Debit, Credit, Transaction, Amount greater/less/equal rules etc.

## Prerequisites

- [Docker](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/install/)
- [Composer](https://getcomposer.org/download/)

## Installation

1. **Clone the Repository**

   ```bash
   git clone https://github.com/Oktobrfest/zash.git
   cd zash
   set .env passwords
   You can use saltgen.py to generate them or just use it to 
   gen the strings for the security.salt and security.cipherSeed here: 
   app/Config/core.php.
   I'm sorry there's probably some details I'm missing here, it's not turn-key, email me if you run into issues.
   Follow these instructions to setup your directories with CakePHP, BoostCake, etc.
   https://webzash.org/develop.html
   Initialize composer.
   docker-compose up
   profit.
   
Note this is an Alpha release. It works as intended but isn't extensively tested and refined!

