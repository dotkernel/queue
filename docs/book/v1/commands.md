# Available commands and usage

The commands available are:

1. `GetFailedMessagesCommand.php (failed)` - returns logs with messages that failed to process (levelName:error)
2. `GetProcessedMessagesCommand.php (procecssed)` - returns logs with messages that were successfully processed (levelName:info)
3. `GetQueuedMessagesCommand (inventory)` - returns all queued messages from Redis stream 'messages'

The commands can be run in two different ways:

### CLI

To run the commands via CLI, use the following syntax:

`php bin/cli.php failed --start="yyyy-mm-dd" --end="yyyy-mm-dd" --limit=int`

`php bin/cli.php processed --start="yyyy-mm-dd" --end="yyyy-mm-dd" --limit=int`

`php bin/cli.php inventory`

### TCP message

To use commands using TCP messages the following messages can be used:

`echo "failed --start=yyyy-mm-dd --end=yyyy-mm-dd --limit=days" | socat -t1 - TCP:host:port`

`echo "processed --start=yyyy-mm-dd --end=yyyy-mm-dd --limit=days" | socat -t1 - TCP:host:port`

In both cases the flags are optional. Keep in mind if both `start` and `end` are set, `limit` will not be applied, it's only used when one of `start` or `end` is missing.

In order to be able to test the `processed` command, by default when processing the "control" message, it is logged as successfully processed with `"levelName":"info"` simulating that the message was processed successfully. To use it run the following message:

`echo "control" | socat -t1 - TCP:host:port`

 > Using `-t1` flag is not necessary but can be useful, it is used to set a timeout of n seconds for both reading and writing, after n second of inactivity, socat will terminate the connection. If the timeout is not set and the server does not respond or keep the connection open, the socat process could hang indefinitely.

`echo "inventory" | socat -t1 - TCP:host:port`
