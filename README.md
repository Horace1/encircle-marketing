# PHP Tyre Scraper – Technical Test for Encircle Marketing

This project is a simple, ethical PHP web scraper built as part of a technical test for Encircle Marketing.

It fetches tyre product data from [Oponeo UK](https://www.oponeo.co.uk) based on predefined tyre size input, stores the results in a MySQL database, and exports them to a CSV file.

---

## Technologies Used

- PHP (Plain PHP, no frameworks)
- cURL for HTTP requests
- DOMDocument & XPath for HTML parsing
- MySQL with `mysqli` extension
- CSV file generation

## Features

- Scrapes up to 20 tyre listings from Oponeo UK using a given tyre size
- Extracts:
    - Website name
    - Tyre brand
    - Pattern
    - Size
    - Price
    - Rating
- Prevents duplicate records using a database lookup
- Exports scraped data to a `tyres_export.csv` file
- Includes ethical scraping practices (1–3 second delay per request)

## Setup Instructions

### 1. Clone the Repository
```bash
git clone https://github.com/Horace1/encircle-marketing.git