// the protocol
// like HTTP/1.1, it has header and body
// header is json and end by LF
// body is binary

package main

import "os"
import "fmt"
import "net"
import "log"
import "bufio"
// import "hyperjson"
import "io/ioutil"

func main() {
	log.SetFlags(log.LstdFlags | log.Lshortfile)

	if len(os.Args) <= 1 {
		fmt.Printf("Usage: %s <port>\n", os.Args[0])
		return
	}

	port := os.Args[1]
	ln, err := net.Listen("tcp", ":"+port)
	handle_error(err)
	fmt.Printf("Listen on :%s\n", port)
	for {
		conn, err := ln.Accept()
		handle_error(err)
		fmt.Println("handleConnection")
		// handleConnection(conn)
		go handleConnection(conn)
	}
}

type Response struct {
	code int
}
func handleConnection(conn net.Conn) {
	for {
		rd := bufio.NewReader(conn)
		hjrd := NewReader(rd) // hyper json reader
		header := hjrd.ReadHeader()
		fmt.Printf("recieve %+v\n", header)

		cc, ok := header["cc"] // connection close
		if ok && cc.(bool) == true {
			fmt.Println("connection close")
			break;
		}

		fp, ok := header["fp"] // file path
		if !ok {
			log.Fatal("no fp(file path)")
		}
		rcl, ok := header["cl"] // content-length, raw
		if !ok {
			log.Fatal("no cl (content-length)")
		}
		cl := int(rcl.(float64))
		if cl != 0 {
			bf := hjrd.ReadBody(cl)
			fmt.Printf("file: %s put content:\n%s\n", fp.(string), string(bf))
			err := ioutil.WriteFile(fp.(string), bf, os.ModePerm)
			if err != nil {
				log.Fatal(err)
			} 
		}
		wt := bufio.NewWriter(conn)
		w := NewWriter(wt)
		res := map[string]interface{} {
			"code": 0,
		}
		w.WriteHeader(res)
	}
	conn.Close()
}
func handle_error(err error) {
	if err != nil {
		log.Fatal(err)
	}
}
