echo -n "Enter username: "
read username
echo -n "Enter password: "
read -s password
php -r "file_put_contents('storage/users/$username.json', json_encode(['password'=>password_hash('$password', PASSWORD_BCRYPT)]));"
read -p "done"
