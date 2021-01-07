read -sp "ccerypt key: " CCRYPT_KEY
ccrypt -d -K "$CCRYPT_KEY" secret.tar.gz
tar -xzvf secret.tar.gz
# Re-encrypt immediately
ccrypt -e -K "$CCRYPT_KEY" secret.tar.gz