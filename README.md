dbseeker
===============

A simple php cli tool to search word patterns in a whole mysql database and replace them if wanted


# install
```
git clone git@github.com:korby/dbseeker.git
```

# Usage
```
./dbseeker.php  [-h host] -u user [-p password] -d databasename -s pattern [-r replacement_pattern]
```

# Example
```
./dbseeker.php  -u myusername -d mydatabase -s "something"
```
