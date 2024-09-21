import secrets
import string

# Generate a secure random salt (alphanumeric)
salt = ''.join(secrets.choice(string.ascii_letters + string.digits) for i in range(64))

# Generate a secure random cipherSeed (numeric)
cipher_seed = ''.join(secrets.choice(string.digits) for i in range(32))

print("Security.salt: ", salt)
print("Security.cipherSeed: ", cipher_seed)
