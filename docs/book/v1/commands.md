## Available commands and usage

The commands available are:

1. `GetFailedMessagesCommand.php` - returns logs with messages that failed to process (levelName:error)
2. `GetProcessedMessagesCommand.php` - returns logs with messages that were successfully processed (levelName:info)

Both commands are used to extract data that can be filtered by period from the log file. The commands can be run in two different ways:

### CLI

To run the commands via CLI, use the following syntax:

`php bin/cli.php failed --start="yyy-mm-dd" --end="yyy-mm-dd" --limit=int`

`php bin/cli.php processed --start="yyy-mm-dd" --end="yyy-mm-dd" --limit=int`

### TCP message

To use commands using TCP messages the following messages can be used:

`echo "failed --start=yyy-mm-dd --end=yyy-mm-dd --limit=days" | socat - TCP:host:port`

`echo "processed --start=yyy-mm-dd --end=yyy-mm-dd --limit=days" | socat - TCP:host:port`

In both cases the flags are optional. Keep in mind if both `start` and `end` are set, `limit` will not be applied, it's only used when one of `start` or `end` is missing.
