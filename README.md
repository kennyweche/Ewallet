# README #

First prototype of the EWallet ( v1)

### What is EWallet for? ###

* EWallet works just like a bank by managing customer(s) and organization(s) funds ( real funds translated to virtual money ). EWallet also works in conjunction with the Payment Gateway to move money from one client to another client.

* Version 1.0.1


### How do I get set up? ###

* Install Apache/nginx web server.
* Install MySQL server and import the database contained in the db folder.
* Install PHP and the relevant modules.
* Install RabbitMQ and Redis Server as well.
* Download, extract set up the EWallet on the Apache web folder.
* Open the Configs.php and setup the database configurations with the relevant. 
* The tests are contained in the Tests folder. In addition the tests cover, deposit, balance and withdrawal.
* That's it, you are good to go. Happy Testing !

### Database setup
* If you need to start clean after transacting use the following query on the eWallet database, ( set foreign_key_checks=0;truncate accounts;truncate accounts_history;truncate customerDetails;truncate customers; truncate organizations;truncate transactions;truncate transactions_history;set foreign_key_checks=1 )

### Contribution guidelines ###

* Feel free to check out the master and create your own branch and make changes

### Who do I talk to? ###

* Kennedy Waweru
