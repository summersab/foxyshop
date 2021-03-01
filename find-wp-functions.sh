#!/bin/bash

if [ ! -e "$1" ]
then
	echo Invalid file or directory.
	exit
fi

i=0

while read p; do
	func=`echo "$p" | cut -f2`
	echo "$func" | grep -q '^#'

	if [[ $? == 0 ]]
	then
		continue
	fi

	grep -qr "^[[:space:]]*$func[[:space:]]*(\|=[[:space:]]*$func[[:space:]]**(\|\.[[:space:]]*$func[[:space:]]*(" "$1"

	if [[ $? == 0 ]]
	then
		if [ -d "$1" ]
		then
			if [[ $i == 1 ]]
			then
				echo
			fi
			echo $func
	        grep -rl "^[[:space:]]*$func[[:space:]]*(\|=[[:space:]]*$func[[:space:]]**(\|\.[[:space:]]*$func[[:space:]]*(" "$1"
			i=1
		elif [ -f "$1" ]
		then
            echo $func
		fi
	fi
done < wp-functions.txt
