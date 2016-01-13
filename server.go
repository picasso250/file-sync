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
import "io/ioutil"
import "encoding/json"

func main() {
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
		go handleConnection(conn)
	}
}

type Response struct {
	code int
}
func handleConnection(conn net.Conn) {
	for {
		rd := bufio.NewReader(conn)
		hjrd := NewHyperJsonReader(rd)
		header := hjrd.ReadHeader()
		fp, ok := header["fp"] // file path
		if !ok {
			log.Fatal("no fp(file path)")
		}
		cl, ok := header["cl"] // content-length
		if !ok {
			log.Fatal("no cl (content-length)")
		}
		if cl.(int) != 0 {
			bf := hjrd.ReadBody(cl.(int))
			err := ioutil.WriteFile(fp.(string), bf, os.ModePerm)
			handle_error(err)
		}
		wt := bufio.NewWriter(conn)
		w := NewWriter(wt)
		res := Response {
			code: 0,
		}
		w.WriteResponse(res)
		cc, ok := header["cc"] // connection close
		if ok && cc.(bool) == true {
			break;
		}
	}
}
func handle_error(err error) {
	if err != nil {
		log.Fatal(err)
	}
}
