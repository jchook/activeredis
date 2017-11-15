BEGIN {
	red="\033[31m"
	green="\033[32m"
	bold="\033[1m"
	invert="\033[7m"
	reset="\033[0m"
	n="\n"
}
{ print $0 }
/(ERRORS!)/ { print n red bold invert "FAIL" reset n }
/(OK)/ { print n green bold invert "PASS" reset n }
