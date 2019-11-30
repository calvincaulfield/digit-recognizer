set -x
curl -o ./ecs-cli https://amazon-ecs-cli.s3.amazonaws.com/ecs-cli-linux-amd64-latest
echo "$(curl -s https://amazon-ecs-cli.s3.amazonaws.com/ecs-cli-linux-amd64-latest.md5) ./ecs-cli" | md5sum -c -
apt install gnupg -y
gpg --keyserver hkp://keys.gnupg.net --recv BCE9D9A42D51784F
curl -o ecs-cli.asc https://amazon-ecs-cli.s3.amazonaws.com/ecs-cli-linux-amd64-latest.asc
gpg --verify ecs-cli.asc ./ecs-cli
chmod +x ./ecs-cli
./ecs-cli --version
