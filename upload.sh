upload() {
	for file in $1/*
	do
		echo $file
		if [[ -d $file ]]; then
			upload $file
		else
			curl -F "f=@$file" -F "action=upload_file" -F "dest=/var/www" http://localhost/http_server.php
		fi
	done
}

upload $1
