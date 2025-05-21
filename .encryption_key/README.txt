Generate key with

dd if=/dev/urandom bs=32 count=1 | base64 -i - > .encryption_key/encrypt.key
