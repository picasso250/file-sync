package main

import "bufio"
import "encoding/json"

type Reader struct {
	rd *bufio.Reader
}

func NewReader(rd *bufio.Reader) *Reader {
	r := new(Reader)
	r.reset(rd)
	return r
}
func (b *Reader) ReadHeader() (header map[string]interface{}) {
	rjh, err := b.rd.ReadSlice('\n') // raw json header
	handle_error(err)
	header = make(map[string]interface{})
	err = json.Unmarshal(rjh, &header)
	handle_error(err)
	return header
}
func (b *Reader) ReadBody(n int) (bf []byte) {
	bf = make([]byte, n)
	for len(bf) < n {
		c, err := b.rd.ReadByte()
		handle_error(err)
		bf = append(bf, c)
	}
	return bf
}
func (b *Reader) reset(rd *bufio.Reader) {
	*b = Reader{
		rd: rd,
	}
}

type Writer struct {
	wt *bufio.Writer
}

func NewWriter(wt *bufio.Writer) *Writer {
	w := new(Writer)
	*w = Writer{
		wt: wt,
	}
	return w
}
func (wt *Writer) WriteHeader(header map[string]interface{}) {
	b, err := json.Marshal(header)
	handle_error(err)
	_, err = wt.wt.Write(b)
	handle_error(err)
	err = wt.wt.WriteByte('\n')
	handle_error(err)
	err = wt.wt.Flush()
	handle_error(err)
}
func (wt *Writer) WriteBody(b []byte) {
	_, err := wt.wt.Write(b)
	handle_error(err)
	err = wt.wt.Flush()
	handle_error(err)
}
