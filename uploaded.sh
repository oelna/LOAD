#!/bin/bash

if [ $# -ne 4 ]; then
	echo $0 takes 4 arguments:
	echo [uploaded-id] [uploaded-password] [url] [target_dir]
	exit 1
fi

uploaded_userid=$1
uploaded_password=$2
download_url=$3
target_dir=$4

cd $target_dir
#get the cookie auth data
wget -qO- --save-cookies cookies.txt --keep-session-cookies --post-data "id=$uploaded_userid&pw=$uploaded_password" http://uploaded.net/io/login &> /dev/null

#get the file info from the html page manually
file_info=$(curl "$download_url" 2>/dev/null)
regex="filename \= '([0-9a-zA-Z.]+)'"

if [[ "$file_info" =~ $regex ]]; then
   name="${BASH_REMATCH[1]}"
   filename=${name}
else
   filename="download_$(date +"+%Y%m%d_%H%M%S")"
fi

#download the actual file
wget -qO- --load-cookies cookies.txt -p "$download_url" -O $filename

#clean up after ourselves
rm ./cookies.txt
