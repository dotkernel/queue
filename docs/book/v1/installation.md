## INSTALLATION 

### git clone

`git clone -b default-queue https://github.com/dotkernel/queue.git`

### Edit .dist files from config/autoload folder

- local.php.dist
- log.local.dist
- messenger.local.php.dist
- swoole.local.php.dist

## Run Composer 

`composer install --no-dev`

###  Create services ( daemon)

- Edit the files from `/daemon` folder and set proper paths
- copy them in /etc/systemd/system/

`sudo cp /home/dotkernel/queue/daemon\swoole.service`

`sudo cp /home/dotkernel/queue/daemon\messenger.service`


### Start the daemon 

`sudo systemctl daemon-reload`

`sudo systemctl enable swoole.service`

`sudo systemctl start swoole.service`

`sudo systemctl status  swoole.service`


### Testing the installation

Send a request from your local machine 

`echo "Hello" | socat -T1 - TCP:SERVER-IP:8556`
