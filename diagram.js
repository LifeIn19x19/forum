function WriteToFile(value) {
var fso, s;
fso = new ActiveXObject("Scripting.FileSystemObject");
s = fso.CreateFolder("c://xampp/htdocs/phpbb3/sgf/test.txt", true);
s.writeline(value);
s.Close();
}

