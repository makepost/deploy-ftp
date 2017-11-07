# deploy-ftp

Uploads files and db as archive over FTP.

## Usage

```bash
composer global require makepost/deploy-ftp

cd /path/to/your/git/repo

# In Ubuntu terminal, lines starting with space don't go to history.
 export BASE_URI=http://example.com
 export PUBLIC_HTML=ftp://user:pass@ftp.example.com/public_html
 export DB=mysql://user:pass@mysql.example.com/db

# Gets a snapshot you can work on.
deploy-ftp get
deploy-ftp get-db

# Unpacks your local files over remote.
deploy-ftp put
deploy-ftp put-db
```

## License

MIT
