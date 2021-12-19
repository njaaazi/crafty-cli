# crafty-cli

## Requirements
- PHP (Version 7.3 or greater)

## Run Crafty
In order to run crafty, go to `./builds` directory and run `php crafty`. You will see all the commands available from crafty.

### Crafty commands and options available
- run `php crafty` command to show all comands available
- run `php crafty packages --help` option to see all available options for packages options
- run `php crafty packages` option to show packages for craft-cms, it will show on a table format inside terminal
- run `php crafty packages --limit={number}` option to only show desired number of craft-cms packages, you can set the number desired to show packages. If you don't specify `--limit` option by default it will show 50 packages
- run `php crafty packages --orderBy={column}` option to order by desired column. Columns available on which you can order by are: 
    -  downloads (default)
    -  favers
    -  dependents
    -  updated
- run `php crafty packages --orderBy=downloads --ASC` option to order in ascending, by default is in descending
- run `php crafty packages --output` option to save results as a json file format

 
## Run Crafty on terminal from anywhere
In order to run crafty anywhere in terminal, please follow these steps: 
- Run this: ```sudo cp ./builds/crafty /usr/local/bin```
- Restart terminal
- Run ```crafty``` command