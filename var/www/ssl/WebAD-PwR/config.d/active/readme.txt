to enable a config folder use the following command where 'default' is the config folder you want to load/activate

ln -s ../available/default/* ./

to remove a link use the 'unlink' command eg

find -type l -exec unlink {} \;

