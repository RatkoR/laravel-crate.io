echo '---- starting docker container crate ----'
docker run -d --name=crate01 \
      -p 4201:4200 \
      --env CRATE_HEAP_SIZE=2g \
      crate -Cnetwork.host=_site_ \
            -Cdiscovery.type=single-node

echo '---- waiting for docker container crate to start ----'
sleep 5

echo '---- start running tests ----'

./vendor/bin/phpunit

echo '---- finished running tests ----'

echo '---- removing crate container ----'

docker rm --force crate01

echo '---- removed crate container ----'
