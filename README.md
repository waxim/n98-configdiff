# Config Diff
This is a plugin for N98 that will allow you to diff the `core_config_data` table with that of a remote. It will list all values that are different in local than in remote. You can also use the `--show-missing` flag to list values found locally that were not found remotely.

## Installation

See https://github.com/netz98/n98-magerun/wiki/Modules#where-can-modules-be-placed

Something like this should work.

```
mkdir ~/.n98-magerun/modules && cd ~/.n98-magerun/modules && git clone https://github.com/waxim/n89-configdiff configdiff
```

## Usage

Show different values.

```
n98-magerun config:diff --dsn="mysql:host=localhost;port=3307;dbname=testdb" --username="root" --password="root"
```

Only show only missing values.

```
n98-magerun config:diff --dsn="mysql:host=localhost;port=3307;dbname=testdb" --username="root" --password="root" --show-missing=1 --show-different=0
```

> Note: Output to console for large tables can be slow, so consider piping the output to a file using ` > filename.txt` at the end of the command. 

### Full Options

| Option | Description | Example |
| --- | --- | --- | 
| `--dsn` | Required. The DSN for the remote database | `mysql:host=localhost;port=3307;dbname=testdb;charset=UTF8` |
| `--username` | Required. Databse Username | `root`|
| `--password` | Database Password | `root`|
| `--prefix` | If the remote magento db uses a prefix, pass it with this. | `magento`|
| `--show-different` | Defaults to 1. Set to 0 to hide different values. Useful if you only want to know missing values. | `0`|
| `--show-missing` | Defaults to 0. Set to 1 to include missing rows in remote. |`0`|
| `--scope` | Limit the scope. `website`,`store`,`global` | `website`|
| `--scope-id` | The id of the scope limit | `2`|
