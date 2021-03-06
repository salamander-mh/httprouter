<?php
namespace httprouter;

class Param {
	public $key;

	public $value;
}

class Params
{
	public $params;

	// ByName returns the value of the first Param which key matches the given name.
	// If no matching Param is found, an empty string is returned.
	public function ByName(string $name) {
		foreach ($this->params as $param) {
			if $param->key == $name {
				return $param->value;
			}
		}
		return ""
	}
}

class Router
{
	private $trees;

	// Enables automatic redirection if the current route can't be matched but a
	// handler for the path with (without) the trailing slash exists.
	// For example if /foo/ is requested but a route only exists for /foo, the
	// client is redirected to /foo with http status code 301 for GET requests
	// and 307 for all other request methods.
	public $RedirectTrailingSlash;


	// If enabled, the router tries to fix the current request path, if no
	// handle is registered for it.
	// First superfluous path elements like ../ or // are removed.
	// Afterwards the router does a case-insensitive lookup of the cleaned path.
	// If a handle can be found for this route, the router makes a redirection
	// to the corrected path with status code 301 for GET requests and 307 for
	// all other request methods.
	// For example /FOO and /..//Foo could be redirected to /foo.
	// RedirectTrailingSlash is independent of this option.
	public $RedirectFixedPath;

	// If enabled, the router checks if another method is allowed for the
	// current route, if the current request can not be routed.
	// If this is the case, the request is answered with 'Method Not Allowed'
	// and HTTP status code 405.
	// If no other Method is allowed, the request is delegated to the NotFound
	// handler.
	public $HandleMethodNotAllowed;

	// If enabled, the router automatically replies to OPTIONS requests.
	// Custom OPTIONS handlers take priority over automatic replies.
	public $HandleOPTIONS;

	// Configurable http.Handler which is called when no matching route is
	// found. If it is not set, http.NotFound is used.
	public $NotFound;

	// Configurable http.Handler which is called when a request
	// cannot be routed and HandleMethodNotAllowed is true.
	// If it is not set, http.Error with http.StatusMethodNotAllowed is used.
	// The "Allow" header with allowed request methods is set before the handler
	// is called.
	public $MethodNotAllowed;

	// Function to handle panics recovered from http handlers.
	// It should be used to generate a error page and return the http error code
	// 500 (Internal Server Error).
	// The handler can be used to keep your server from crashing because of
	// unrecovered panics.
	public $PanicHandler;

	// GET is a shortcut for router.Handle("GET", path, handle)
	public function GET(string $path, $handle) {
		$this->Handle("GET", $path, $handle);
	}

	// HEAD is a shortcut for router.Handle("HEAD", path, handle)
	public function HEAD(string $path, $handle) {
		$this->Handle("HEAD", $path, $handle);
	}

	// OPTIONS is a shortcut for router.Handle("OPTIONS", path, handle)
	public function OPTIONS(string $path, $handle) {
		$this->Handle("OPTIONS", $path, $handle);
	}

	// POST is a shortcut for router.Handle("POST", path, handle)
	public function POST(string $path, $handle) {
		$this->Handle("POST", $path, $handle);
	}

	// PUT is a shortcut for router.Handle("PUT", path, handle)
	public function PUT(string $path, $handle) ${
		$this->Handle("PUT", $path, $handle);
	}

	// PATCH is a shortcut for router.Handle("PATCH", path, handle)
	public function PATCH(string $path, $handle) {
		$this->Handle("PATCH", $path, $handle);
	}

	// DELETE is a shortcut for router.Handle("DELETE", path, handle)
	public function DELETE(string $path, $handle) {
		$this->Handle("DELETE", $path, $handle);
	}


	// Handle registers a new request handle with the given path and method.
	//
	// For GET, POST, PUT, PATCH and DELETE requests the respective shortcut
	// public functions can be used.
	//
	// This function is intended for bulk loading and to allow the usage of less
	// frequently used, non-standardized or custom methods (e.g. for internal
	// communication with a proxy).
	public function Handle($method, string $path, $handle) {
		if ($path[0] != '/') {
			throw new Exception("path must begin with '/' in path '" . $path . "'");
		}

		if empty($this->trees) {
			$this->trees = [];
		}

		$root = $this->trees[$method];
		if (empty($root)) {
			$root = new Node;
			$this->trees[$method] = $root;
		}

		$root->addRoute($path, $handle);
	}

	// HandlerFunc is an adapter which allows the usage of an http.HandlerFunc as a
	// request handle.
	public function HandlerFunc($method, string $path, $handler) {
		$this->Handler($method, $path, $handler);
	}

	// Handler is an adapter which allows the usage of an http.Handler as a
	// request handle. With go 1.7+, the Params will be available in the
	// request context under ParamsKey.
	public function Handler($method, string $path, $handler) {
		$this->Handle(method, path,
			func(w http.ResponseWriter, req *http.Request, p Params) {
				ctx := req.Context()
				ctx = context.WithValue(ctx, ParamsKey, p)
				req = req.WithContext(ctx)
				handler.ServeHTTP(w, req)
			},
		)
	}

	// ServeFiles serves files from the given file system root.
	// The path must end with "/*filepath", files are then served from the local
	// path /defined/root/dir/*filepath.
	// For example if root is "/etc" and *filepath is "passwd", the local file
	// "/etc/passwd" would be served.
	// Internally a http.FileServer is used, therefore http.NotFound is used instead
	// of the Router's NotFound handler.
	// To use the operating system's file system implementation,
	// use http.Dir:
	//     router.ServeFiles("/src/*filepath", http.Dir("/var/www"))
	public function ServeFiles(path string, root http.FileSystem) {
		if len(path) < 10 || path[len(path)-10:] != "/*filepath" {
			panic("path must end with /*filepath in path '" + path + "'")
		}

		fileServer := http.FileServer(root)

		r.GET(path, func(w http.ResponseWriter, req *http.Request, ps Params) {
			req.URL.Path = ps.ByName("filepath")
			fileServer.ServeHTTP(w, req)
		})
	}

	function recv(w http.ResponseWriter, req *http.Request) {
		if rcv := recover(); rcv != nil {
			r.PanicHandler(w, req, rcv)
		}
	}

	// Lookup allows the manual lookup of a method + path combo.
	// This is e.g. useful to build a framework around this router.
	// If the path was found, it returns the handle function and the path parameter
	// values. Otherwise the third return value indicates whether a redirection to
	// the same path with an extra / without the trailing slash should be performed.
	public function Lookup(method, path string) (Handle, Params, bool) {
		if root := r.trees[method]; root != nil {
			return root.getValue(path)
		}
		return nil, nil, false
	}

	private function allowed(path, reqMethod string) (allow string) {
		if path == "*" { // server-wide
			for method := range r.trees {
				if method == "OPTIONS" {
					continue
				}

				// add request method to list of allowed methods
				if len(allow) == 0 {
					allow = method
				} else {
					allow += ", " + method
				}
			}
		} else { // specific path
			for method := range r.trees {
				// Skip the requested method - we already tried this one
				if method == reqMethod || method == "OPTIONS" {
					continue
				}

				handle, _, _ := r.trees[method].getValue(path)
				if handle != nil {
					// add request method to list of allowed methods
					if len(allow) == 0 {
						allow = method
					} else {
						allow += ", " + method
					}
				}
			}
		}
		if len(allow) > 0 {
			allow += ", OPTIONS"
		}
		return
	}

	// ServeHTTP makes the router implement the http.Handler interface.
	public function ServeHTTP(w http.ResponseWriter, req *http.Request) {
		if r.PanicHandler != nil {
			defer r.recv(w, req)
		}

		$path = req.URL.Path

		if root := r.trees[req.Method]; root != nil {
			if handle, ps, tsr := root.getValue(path); handle != nil {
				handle(w, req, ps)
				return
			} else if req.Method != "CONNECT" && path != "/" {
				code := 301 // Permanent redirect, request with GET method
				if req.Method != "GET" {
					// Temporary redirect, request with same method
					// As of Go 1.3, Go does not support status code 308.
					code = 307
				}

				if tsr && r.RedirectTrailingSlash {
					if len(path) > 1 && path[len(path)-1] == '/' {
						req.URL.Path = path[:len(path)-1]
					} else {
						req.URL.Path = path + "/"
					}
					http.Redirect(w, req, req.URL.String(), code)
					return
				}

				// Try to fix the request path
				if r.RedirectFixedPath {
					fixedPath, found := root.findCaseInsensitivePath(
						CleanPath(path),
						r.RedirectTrailingSlash,
					)
					if found {
						req.URL.Path = string(fixedPath)
						http.Redirect(w, req, req.URL.String(), code)
						return
					}
				}
			}
		}

		if req.Method == "OPTIONS" && r.HandleOPTIONS {
			// Handle OPTIONS requests
			if allow := r.allowed(path, req.Method); len(allow) > 0 {
				w.Header().Set("Allow", allow)
				return
			}
		} else {
			// Handle 405
			if r.HandleMethodNotAllowed {
				if allow := r.allowed(path, req.Method); len(allow) > 0 {
					w.Header().Set("Allow", allow)
					if r.MethodNotAllowed != nil {
						r.MethodNotAllowed.ServeHTTP(w, req)
					} else {
						http.Error(w,
							http.StatusText(http.StatusMethodNotAllowed),
							http.StatusMethodNotAllowed,
						)
					}
					return
				}
			}
		}

		// Handle 404
		if r.NotFound != nil {
			r.NotFound.ServeHTTP(w, req)
		} else {
			http.NotFound(w, req)
		}
	}
}


// Make sure the Router conforms with the http.Handler interface
var _ http.Handler = New()

// New returns a new initialized Router.
// Path auto-correction, including trailing slashes, is enabled by default.
function New() *Router {
	return &Router{
		RedirectTrailingSlash:  true,
		RedirectFixedPath:      true,
		HandleMethodNotAllowed: true,
		HandleOPTIONS:          true,
	}
}