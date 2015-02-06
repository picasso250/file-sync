upload() {
	cd $1
	for file in ./*
	do
		echo $file
		if [[ -d $file ]]; then
			cd $file
			upload . $2/$file
		else
			curl -F "f=@$file" -F "action=upload_file" -F "dest=$2/$1" http://localhost/http_server.php
		fi
	done
	cd ..
}

echo send $1 to $2
upload $1 $2 # source dest
