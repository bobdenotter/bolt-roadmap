Bolt Roadmap
------------

A repo to keep track of the Bolt Roadmap.

 - Github Repository: https://github.com/bolt/four
 - Bolt 4 roadmap: http://bit.ly/bolt4-roadmap
 - Planboard: http://bit.ly/bolt4-board (open for all, requires Github Auth) 
 
 
 Installation
 ------------
 
  - Clone repo
  - Run `composer install`
  - Run `bin/console app:github` to fetch data
  > Note that to run `bin/console app:github` you must use one of the following authentication methods: 
1. **Token authentication** by adding your personal Github token to the `GITHUB_SECRET` environment variable in the `.env` file. 
2. **Username/Password** by setting your username on `GITHUB_USERNAME` and your password on `GITHUB_SECRET` environment variables in the `.env` file. __This method will not work if you have Two Factor Authentication enabled.__
  - Run `bin/console server:start` to start a webserver, and see the results in a browser
  
  
