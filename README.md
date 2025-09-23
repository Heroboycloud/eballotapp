#  Eballot system in php

## uses php and mysql

*Developed by Akindel*

Call me on [08020368811](tel:08020368811)

>  Designed to read through excel files using phpspreadsheet


1. Added support for mysql
2. Added support for postgresql
3. Added bootstrap npm stuff



# How to start

1. create database named eballot in phpmyadmin
2. import `ballot.sql` in the database
3. save this folder to htdocs as eballot
4. Visit localhost/eballot/admin.php
5. it will redirect you to login
6. enter the details or edit it on `userpass.php`
7. next, login
8. admin page will be shown
9. Next, select Payment 1 and upload excel sheet
10. Wait for success 
11. Select Payment 2 and upload second excel sheet
12. Tell each user their voter id .... they need it for voting
12. Next, edit in your sql the people you want to vote for
13. that is it
14. Refresh to show voters


# As for voters
1. go to localhost/eballot
2. collect your voter id
3. enter it correctly
4. login and select who to vote for
5. you cannot revote... so choose carefully

