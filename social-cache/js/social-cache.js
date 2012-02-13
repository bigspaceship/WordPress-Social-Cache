jQuery(document).ready(function($) {
	(function() {
		var self = this;
		var rootEl = $("#post-body");
		var socialCacheMetaboxEl = $("#social-cache-wrapper");

		rootEl.find("#bss_social_cache_url").removeClass("hide-if-js");
		rootEl.find("#postexcerpt").removeClass("hide-if-js");

		self.dateUtils = {};
		dateUtils.twoDigits = function(d) {
			if(0 <= d && d < 10) return "0" + d.toString();
    		if(-10 < d && d < 0) return "-0" + (-1*d).toString();
    		return d.toString();
		}

		Date.prototype.mysqlFormat = function() { return this.getUTCFullYear() + "-" + dateUtils.twoDigits(1 + this.getUTCMonth()) + "-" + dateUtils.twoDigits(this.getUTCDate()) + " " + dateUtils.twoDigits(this.getUTCHours()) + ":" + dateUtils.twoDigits(this.getUTCMinutes()) + ":" + dateUtils.twoDigits(this.getUTCSeconds()); };

		self.facebook = {};
		self.facebook.appId = BSS_SOCIAL_CACHE_SETTINGS.facebook_app_id;
		self.facebook.appSecret = BSS_SOCIAL_CACHE_SETTINGS.facebook_app_secret;
		self.facebook.accessToken = undefined;
		self.facebook.loadComplete = function($result) { self.populateForm($result); };
		self.facebook.load = function($username,$statusId) {
			$.get("https://graph.facebook.com/" + $username,{access_token: self.facebook.accessToken },function(user) {
				$.get("https://graph.facebook.com/" + user.id + "_" + $statusId,{access_token: self.facebook.accessToken },function(post) {
					var result = {};
					result.type = "facebook";
					result.username = post.from.name;
					result.message = post.message;
					result.jsonString = JSON.stringify(post);
					var created = new Date(Date.parse(post.created_time))
					result.datePublished = created.mysqlFormat();						

					self.facebook.loadComplete(result);
				},"json");
			},"json");
		}

		if(self.facebook.appId == "" || self.facebook.appSecret == "") {
			alert("Warning :: You cannot quickly add Facebook messages until you update the settings.");
		}

		self.twitter = {};
		self.twitter.loadComplete = function($result) { self.populateForm($result); }
		self.twitter.load = function($username,$statusId) {
			$.get("https://api.twitter.com/1/statuses/show.json?id="+ $statusId +"&include_entities=true&callback=?",undefined,function(tweet) {
				var result = {};
				result.type = "twitter";
				result.username = tweet.user.screen_name;
				result.message = tweet.text;
				result.jsonString = JSON.stringify(tweet);

				// jk: handle the twitter date
				var created = new Date(Date.parse(tweet.created_at))
				result.datePublished = created.mysqlFormat();	

				self.twitter.loadComplete(result);
			},"json");
		}

		// set up default text hide/show
		var input = socialCacheMetaboxEl.find("input#social-cache-message-url");
		var defaultText = input.attr('value');

		input.focus(function() {
			if($(this).attr('value') == defaultText) $(this).attr('value','');
		})

		input.blur(function() {
			if($(this).attr('value') == '') $(this).attr('value',defaultText);		
		});

		// skip 
		socialCacheMetaboxEl.find("input#social-cache-skip").click(function() {
			self.showFields();
			return false;
		})

		// go
		socialCacheMetaboxEl.find("input#social-cache-go").click(function() {
			// jk: first check to make sure URL is valid.
			var url = input.attr('value');
			var validate = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
			if(validate.test(url)) {

				var parsedUrl =  parseUri(url);
				// jk: for reference
				/*
					anchor: ""
					authority: "facebook.com"
					directory: "/Chobani/posts/216675195094683"
					file: ""
					host: "facebook.com"
					password: ""
					path: "/Chobani/posts/216675195094683"
					port: ""
					protocol: "http"
					query: ""
					queryKey: Object
					relative: "/Chobani/posts/216675195094683"
					source: "http://facebook.com/Chobani/posts/216675195094683"
					user: ""
				*/

				var host = parsedUrl.host.split("www.").join("");
				switch(host) {
					case "facebook.com":
						var route = parsedUrl.directory.split("/"); // ["","Chobani","post","216675195094683"]
						var username = route[1];
						var statusId = route[3];

						if(!self.facebook.accessToken) {
							$.get("https://graph.facebook.com/oauth/access_token?client_id="+self.facebook.appId+"&client_secret="+self.facebook.appSecret+"&grant_type=client_credentials",undefined,function(data) {
								self.facebook.accessToken = data.split("access_token=").join("");
								self.facebook.load(username,statusId);
							},"text");
						}
						else {
							self.facebook.load(username,statusId);
						}
					break;

					case "twitter.com":
						var route = parsedUrl.anchor.split("/"); // ["!","Chobani","status","abc123"]
						var username = route[1];
						var statusId = route[3];
						self.twitter.load(username,statusId);
					break;

					default:
						alert("Please enter a URL from Facebook or Twitter!");
					break;

				}
			}
			else {
				alert("Please enter a valid URL!");
			}

			return false;
		})

		self.populateForm = function($result) {
			if(!$result) alert("There was an error. Please try again.");
			else {
				// jk: populate
				rootEl.find("#titlediv").find("input#title").val($result.username).trigger('focus');
				rootEl.find("#excerpt").val($result.message);
				rootEl.find('#social-cache-message-type input[type=radio][value="'+ $result.type + '"]').prop("checked",true);
				rootEl.find('#social-cache-date-published').val($result.datePublished);
				rootEl.find('#social-cache-json-cache').val($result.jsonString);

				self.showFields();
			}
		}

		// show actual fields
		self.showFields = function() {
			rootEl.find("#social-cache-wrapper").parents(".postbox").slideUp('fast',function() {
				rootEl.find("#titlediv").slideDown('fast');
				rootEl.find("#postdivrich").slideDown('fast');
				rootEl.find("#normal-sortables").slideDown('fast');
			});
		}

	})();
});