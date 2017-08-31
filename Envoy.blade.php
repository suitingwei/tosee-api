@servers(['web' => 'root@tosee'])

@task('deploy', ['on' => 'web'])
cd /data/www/tosee-api

git pull origin master

php artisan migrate

@endtask
