read -sp "ccerypt key: " CCRYPT_KEY
tar -czvf secret.tar.gz secret/
ccrypt -e -K "$CCRYPT_KEY" secret.tar.gz