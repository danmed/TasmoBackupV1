/**
 * Sorts a column containing IP addresses (IPv4 and IPv6) or IPv4 address and port delimited by ':' in typical dot
 * notation / colon. This can be most useful when using DataTables for a
 * networking application, and reporting information containing IP address.
 *
 *  @name IP addresses 
 *  @summary Sort IP addresses numerically
 *  @author Dominique Fournier
 *  @author Brad Wasson
 *  @author Peter Vilhan
 *
 *  @example
 *    $('#example').dataTable( {
 *       columnDefs: [
 *         { type: 'ip-address', targets: 0 }
 *       ]
 *    } );
 */


jQuery.extend( jQuery.fn.dataTableExt.oSort, {
	"ip-address-pre": function ( a ) {
		let i, item;
		let m, n, t;
		let x, xa;

		if (!a) {
			return 0
		}

		a = a.replace(/<[\s\S]*?>/g, "").replace(/&nbsp;/g,"");
		//IPv4:Port
                n = a.split(":");
                if (n.length == 2){
                        m = t[0].split(".");
                }
                else {
                        m = a.split(".");
                }
		x = 0;
		xa = "";

		if (m.length == 4) {
			// IPV4
			for(i = 0; i < m.length; i++) {
				if(i>0) x*=256;
				x += parseInt(m[i]);
			}
		}
		else if (n.length > 0) {
			x = "";
			// IPV6
			var count = 0;
			for(i = 0; i < n.length; i++) {
				item = n[i];

				if (i > 0) {
					xa += ":";
				}

				if(item.length === 0) {
					count += 0;
				}
				else if(item.length == 1) {
					xa += "000" + item;
					count += 4;
				}
				else if(item.length == 2) {
					xa += "00" + item;
					count += 4;
				}
				else if(item.length == 3) {
					xa += "0" + item;
					count += 4;
				}
				else {
					xa += item;
					count += 4;
				}
			}

			// Padding the ::
			n = xa.split(":");
			var paddDone = 0;

			for (i = 0; i < n.length; i++) {
				item = n[i];

				if (item.length === 0 && paddDone === 0) {
					for (var padding = 0 ; padding < (32-count) ; padding++) {
						x += "0";
						paddDone = 1;
					}
				}
				else {
					x += item;
				}
			}
		}

		return x;
	},

	"ip-address-asc": function ( a, b ) {
		return ((a < b) ? -1 : ((a > b) ? 1 : 0));
	},

	"ip-address-desc": function ( a, b ) {
		return ((a < b) ? 1 : ((a > b) ? -1 : 0));
	},
    "version-pre": function ( d ) {
       d = d.replace(/<[\s\S]*?>/g, "").replace(/&nbsp;/g, "");
       let a = d.split(/[ .]+/), res = "";
       for(var i = 0; i < a.length; i++) {
          res += ("00000000000000000000" + a[i]).slice(-20);
       }
       return res;
    },   
    "version-asc": function ( a, b ) {
        return ((a < b) ? -1 : ((a > b) ? 1 : 0));
    },
    "version-desc": function ( a, b ) {
        return ((a < b) ? 1 : ((a > b) ? -1 : 0));
    }    
});
