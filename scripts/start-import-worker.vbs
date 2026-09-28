Option Explicit
Dim shell, command
Set shell = CreateObject("WScript.Shell")
shell.CurrentDirectory = WScript.Arguments(1)
command = Quote(WScript.Arguments(0)) & " artisan queue:work database --stop-when-empty --tries=1 --timeout=1200 --max-time=1200 --sleep=1 --queue=" & Quote(WScript.Arguments(2))
' Window style 0 keeps the worker hidden; False returns without waiting.
shell.Run command, 0, False

Function Quote(value)
    Quote = Chr(34) & Replace(value, Chr(34), Chr(34) & Chr(34)) & Chr(34)
End Function
