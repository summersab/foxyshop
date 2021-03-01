#!/bin/bash

PATHNAME=""
FUNCTION="[a-zA-Z0-9_-]+"
PRIVATE=0
TYPE=""

while getopts ":hf:p:" opt; do
	case ${opt} in
		h )
			echo "Usage: $0 [OPTION] [PATH]"
			echo "	  -h	Display this help message."
			echo "	  -f	The function to search for."
			echo "	  -p	Whether or not to include private/protected functions. 1 shows the private/protected label; 0 just shows the function. By default, -p 0 is assumed."
			exit 0
			;;
		\? )
			echo "Invalid Option: -$OPTARG" 1>&2
			exit 1
			;;
		: )
			echo "This option requires an argument. See $0 -h for more info."
			exit 1
			;;
		f )
			FUNCTION=${OPTARG}
			;;
		p )
			PRIVATE=1
			;;
	esac
done
shift $((OPTIND-1))
PATHNAME=$@

if [ -f $PATHNAME ] ; then
	TYPE="-n"
elif [ -d $PATHNAME ] ; then
	TYPE="-r"
fi

if [ $PRIVATE -eq 0 ] ; then
	egrep "$TYPE" --include \*.php "^[[:space:]a-z]*function[[:space:]]+"$FUNCTION"[[:space:]]*\([^\}\{]+\)[[:space:]\{]*$" "$PATHNAME" | sed 's/[[:space:]]*(.*//g' | sed 's/public \|static \|final \|abstract \|function \|private \|protected //g' | sed 's/:/\t/g' | sed 's/ //g' | sed 's/\t\$//g' | tr -s '\t' '\t'
elif [ $PRIVATE -eq 1 ] ; then
	egrep "$TYPE" --include \*.php "^[[:space:]a-z]*function[[:space:]]+"$FUNCTION"[[:space:]]*\([^\}\{]+\)[[:space:]\{]*$" "$PATHNAME" | sed 's/[[:space:]]*(.*//g' | sed 's/public \|static \|final \|abstract \|function //g' | sed 's/:/\t/g' | sed 's/\(private\|protected\) /\1\t/g' | sed 's/ //g' | sed 's/\(private\|protected\)\t/\1 /g' | sed 's/\t\$//g' | tr -s '\t' '\t'
else
	egrep "$TYPE" --include \*.php "^[[:space:]a-z]*function[[:space:]]+"$FUNCTION"[[:space:]]*\([^\}\{]+\)[[:space:]\{]*$" "$PATHNAME" | sed '/.*private.*\|.*protected.*/d' | sed 's/[[:space:]]*(.*//g' | sed 's/public \|static \|final \|abstract \|function //g' | sed 's/:/\t/g' | sed 's/ //g' | sed 's/\t\$//g' | tr -s '\t' '\t'
fi
