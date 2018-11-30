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
  - Add your personal Github token to the `GITHUB_SECRET` environment variable in the `.env` file
  - Run `bin/console app:github` to fetch data
  - Run `bin/console server:start` to start a webserver, and see the results in a browser
  
  
