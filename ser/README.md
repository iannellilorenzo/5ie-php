# Encryption Example

This project demonstrates the use of RC4 and RC5 encryption algorithms. Users can encrypt or decrypt data using either RC4 or RC5 by selecting the appropriate options in the form.

## How RC4 Works

RC4 is a stream cipher that uses a variable-length key to initialize a permutation of all 256 possible bytes. The algorithm consists of two main parts: the Key-Scheduling Algorithm (KSA) and the Pseudo-Random Generation Algorithm (PRGA).

1. **Key-Scheduling Algorithm (KSA)**:
   - Initializes a permutation of all 256 possible bytes.
   - Uses the key to produce an initial permutation of the state array.

2. **Pseudo-Random Generation Algorithm (PRGA)**:
   - Generates a pseudo-random stream of bits (key stream).
   - XORs the key stream with the plaintext to produce the ciphertext.

## How RC5 Works

RC5 is a symmetric block cipher with a variable block size, key size, and number of rounds. The algorithm consists of three main parts: key expansion, encryption, and decryption.

1. **Key Expansion**:
   - Expands the user-supplied key into an array of subkeys.
   - Uses magic constants to initialize the subkeys.

2. **Encryption**:
   - Divides the plaintext into blocks.
   - Uses the subkeys to perform a series of bitwise operations and modular additions on the blocks.

3. **Decryption**:
   - Reverses the encryption process using the same subkeys.

## Operators Used

### Bitwise Operators
- `<<` (left shift): Shifts the bits of a number to the left.
- `>>` (right shift): Shifts the bits of a number to the right.
- `^` (bitwise XOR): Performs a bitwise exclusive OR operation.
- `&` (bitwise AND): Performs a bitwise AND operation.
- `|` (bitwise OR): Performs a bitwise OR operation.

### Arithmetic Operators
- `+` (addition): Adds two numbers.
- `-` (subtraction): Subtracts one number from another.
- `%` (modulus): Returns the remainder of a division operation.

### Functions
- `rotateLeft($value, $shift, $wordSize)`: Rotates the bits of `$value` to the left by `$shift` positions.
- `rotateRight($value, $shift, $wordSize)`: Rotates the bits of `$value` to the right by `$shift` positions.

## How to Use

1. Open the `index.php` file in your web browser.
2. Enter the data you want to encrypt or decrypt in the "Insert below your data" field.
3. Optionally, enter a key in the "Insert below your key used for hashing" field. If left blank, a random key will be generated.
4. Select the algorithm (RC4 or RC5) you want to use.
5. Select the operation (Encrypt or Decrypt) you want to perform.
6. Click the "Submit" button to see the results.

## Example

1. **Data**: `Hello, World!`
2. **Key**: `mysecretkey`
3. **Algorithm**: RC4
4. **Operation**: Encrypt

The form will display the encrypted data and the key used for encryption.

## Dependencies

- [Bootstrap](https://getbootstrap.com/) for styling the form.

## License

This project is licensed under the MIT License.