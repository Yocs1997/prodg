# IP logger demo

The repository includes a small PHP example for storing a visitor's IP address in a JSON file and showing it later.

## Files
- `ip-demo.php`: main example script
- `ip.json`: JSON file where IPs are stored (initially empty)

## How it works
- When you visit `ip-demo.php?k=<some-key>`, the script logs your current IP address under the key you provide.
- When you visit `ip-demo.php?mostrar=1&k=<some-key>`, the script reads `ip.json` and prints the IP saved for that key.
- If `ip.json` does not exist yet, it is automatically created with an empty object.

## Local quick start
1. Run a local PHP server in this directory:
   ```bash
   php -S localhost:8000
   ```
2. Save an IP for a key (for example, `client1`) by visiting:
   ```
   http://localhost:8000/ip-demo.php?k=client1
   ```
3. Show the stored IP later with:
   ```
   http://localhost:8000/ip-demo.php?mostrar=1&k=client1
   ```

## Security notes
- The example does not include authentication. Avoid using it on a public site without adding access controls.
- The script uses a shared JSON file; on busy sites you would want a database or file locking.
- Inputs are escaped before display to prevent HTML injection.
