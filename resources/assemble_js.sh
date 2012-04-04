rm jethro_all.js
mv jquery.js jquery.tmp
cat *.js > jethro_all.js
mv jquery.tmp jquery.js
