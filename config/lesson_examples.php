<?php

return [
    1 => [
        'html' => "<h1>Hello, World!</h1>\n<p>This is my first HTML page.</p>",
        'css' => "body {\n  padding: 25px;\n  font-family: Arial, sans-serif;\n}\n\nh1 {\n  color: #5C6AC4;\n}",
    ],
    2 => [
        'html' => "<header>\n  <h1>My School Page</h1>\n</header>\n<main>\n  <h2>Welcome</h2>\n  <p>This content belongs inside the body.</p>\n</main>\n<footer>Created by a student</footer>",
        'css' => "body {\n  margin: 0;\n  font-family: Arial, sans-serif;\n}\nheader, main, footer {\n  padding: 20px;\n}\nheader {\n  background: #5C6AC4;\n  color: white;\n}\nfooter {\n  background: #eef0ff;\n}",
    ],
    3 => [
        'html' => "<article>\n  <h1>A Day at School</h1>\n  <p>Every school day is a chance to learn something new.</p>\n  <h2>Morning Classes</h2>\n  <p>I begin with science and mathematics.</p>\n  <h2>Afternoon Activities</h2>\n  <p>I enjoy working with my classmates.</p>\n</article>",
        'css' => "body {\n  padding: 25px;\n  font-family: Arial, sans-serif;\n  line-height: 1.6;\n}\nh1 { color: #4338ca; }\nh2 { color: #5C6AC4; }
",
    ],
    4 => [
        'html' => "<h1>Important Reminders</h1>\n<p><strong>Submit your project</strong> before Friday.</p>\n<p>Please <em>review your work</em> first.</p>\n<p><mark>Remember:</mark> Save your files often.</p>\n<p>Use the <code>&lt;strong&gt;</code> element for importance.</p>",
        'css' => "body {\n  padding: 25px;\n  font-family: Arial, sans-serif;\n  line-height: 1.7;\n}\ncode {\n  background: #eef0ff;\n  padding: 3px 6px;\n}\nmark { background: #fde68a; }
",
    ],
    5 => [
        'html' => "<div class=\"card\">\n  <img src=\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='480' height='240'%3E%3Crect width='480' height='240' fill='%235C6AC4'/%3E%3Ccircle cx='240' cy='100' r='45' fill='%23fde68a'/%3E%3Cpath d='M0 240 L150 110 L260 205 L350 135 L480 240' fill='%2334d399'/%3E%3C/svg%3E\" alt=\"A colorful drawing of mountains and the sun\">\n  <h1>Explore Nature</h1>\n  <p>Discover beautiful places near you.</p>\n  <a href=\"https://example.com\">Learn more</a>\n</div>",
        'css' => "body {\n  padding: 25px;\n  font-family: Arial, sans-serif;\n}\n.card {\n  max-width: 360px;\n}\nimg {\n  width: 100%;\n  border-radius: 10px;\n}\na { color: #4338ca; }
",
    ],
    6 => [
        'html' => "<h1>My Study Plan</h1>\n<h2>Subjects to Review</h2>\n<ul>\n  <li>HTML and CSS</li>\n  <li>Mathematics</li>\n  <li>Science</li>\n</ul>\n<h2>Steps for Today</h2>\n<ol>\n  <li>Read the lesson</li>\n  <li>Practice the example</li>\n  <li>Submit the activity</li>\n</ol>",
        'css' => "body {\n  padding: 25px;\n  font-family: Arial, sans-serif;\n  line-height: 1.7;\n}\nh1 { color: #5C6AC4; }\nli { margin-bottom: 6px; }
",
    ],
    7 => [
        'html' => "<table>\n  <caption>Weekly Class Schedule</caption>\n  <thead>\n    <tr><th>Day</th><th>Subject</th><th>Time</th></tr>\n  </thead>\n  <tbody>\n    <tr><td>Monday</td><td>HTML</td><td>9:00 AM</td></tr>\n    <tr><td>Wednesday</td><td>CSS</td><td>10:00 AM</td></tr>\n    <tr><td>Friday</td><td>Project</td><td>1:00 PM</td></tr>\n  </tbody>\n</table>",
        'css' => "body {\n  padding: 25px;\n  font-family: Arial, sans-serif;\n}\ntable {\n  width: 100%;\n  border-collapse: collapse;\n}\ncaption {\n  margin-bottom: 10px;\n  font-size: 22px;\n  font-weight: bold;\n}\nth, td {\n  border: 1px solid #c7d2fe;\n  padding: 10px;\n  text-align: left;\n}\nth { background: #e0e7ff; }
",
    ],
    8 => [
        'html' => "<form>\n  <h1>Student Registration</h1>\n  <label for=\"name\">Full name</label>\n  <input id=\"name\" type=\"text\" placeholder=\"Juan Dela Cruz\">\n  <label for=\"email\">Email address</label>\n  <input id=\"email\" type=\"email\" placeholder=\"juan@example.com\">\n  <label for=\"grade\">Grade level</label>\n  <select id=\"grade\"><option>Grade 9</option><option>Grade 10</option></select>\n  <button type=\"submit\">Register</button>\n</form>",
        'css' => "body {\n  padding: 25px;\n  font-family: Arial, sans-serif;\n}\nform { max-width: 380px; }\nlabel {\n  display: block;\n  margin-top: 14px;\n}\ninput, select, button {\n  width: 100%;\n  padding: 10px;\n  margin-top: 5px;\n  box-sizing: border-box;\n}\nbutton {\n  margin-top: 18px;\n  background: #5C6AC4;\n  color: white;\n  border: 0;\n}
",
    ],
    9 => [
        'html' => "<section class=\"profile-card\">\n  <p class=\"eyebrow\">STUDENT PROFILE</p>\n  <h1>Alex Santos</h1>\n  <p>I enjoy learning web design and creating useful websites.</p>\n  <button>View Projects</button>\n</section>",
        'css' => "body {\n  padding: 25px;\n  font-family: Arial, sans-serif;\n  background: #eef0ff;\n}\n.profile-card {\n  max-width: 360px;\n  padding: 24px;\n  background: white;\n  border-radius: 12px;\n}\n.eyebrow { color: #5C6AC4; }\nbutton {\n  padding: 10px 16px;\n  background: #5C6AC4;\n  color: white;\n  border: 0;\n}
",
    ],
    10 => [
        'html' => "<article>\n  <p class=\"label\">STUDENT VOICE</p>\n  <h1>Learning One Step at a Time</h1>\n  <p class=\"intro\">Small improvements each day can lead to excellent results.</p>\n  <p>Clear colors, readable fonts, and good spacing make an article easier to understand.</p>\n</article>",
        'css' => "body {\n  padding: 25px;\n  font-family: Georgia, serif;\n  color: #24304a;\n  background: #fffaf0;\n}\narticle { max-width: 600px; }\n.label {\n  color: #7c3aed;\n  font-family: Arial, sans-serif;\n  font-weight: bold;\n  letter-spacing: 2px;\n}\nh1 { font-size: 36px; }\n.intro {\n  color: #5C6AC4;\n  font-size: 20px;\n}
",
    ],
    11 => [
        'html' => "<h1>Student Clubs</h1>\n<div class=\"cards\">\n  <section><h2>Art Club</h2><p>Create and share original artwork.</p></section>\n  <section><h2>Code Club</h2><p>Build websites and solve problems.</p></section>\n  <section><h2>Music Club</h2><p>Practice and perform together.</p></section>\n</div>",
        'css' => "* { box-sizing: border-box; }\nbody {\n  padding: 25px;\n  font-family: Arial, sans-serif;\n}\nsection {\n  margin: 14px 0;\n  border: 3px solid #a5b4fc;\n  padding: 18px;\n  background: #eef0ff;\n}\nsection h2 { margin-top: 0; }
",
    ],
    12 => [
        'html' => "<nav>\n  <strong>Study Hub</strong>\n  <div><a href=\"#\">Home</a><a href=\"#\">Lessons</a><a href=\"#\">About</a></div>\n</nav>\n<h1>Featured Topics</h1>\n<div class=\"cards\">\n  <article>HTML</article><article>CSS</article><article>Web Design</article>\n</div>",
        'css' => "body {\n  margin: 0;\n  font-family: Arial, sans-serif;\n}\nnav {\n  display: flex;\n  justify-content: space-between;\n  align-items: center;\n  padding: 18px 25px;\n  background: #312e81;\n  color: white;\n}\nnav a {\n  margin-left: 16px;\n  color: white;\n}\nh1 { padding: 0 25px; }\n.cards {\n  display: flex;\n  gap: 15px;\n  padding: 0 25px;\n}\narticle {\n  flex: 1;\n  padding: 30px 15px;\n  background: #e0e7ff;\n  text-align: center;\n}
",
    ],
    13 => [
        'html' => "<h1>Project Gallery</h1>\n<div class=\"gallery\">\n  <article>Profile Page</article>\n  <article>School Website</article>\n  <article>Registration Form</article>\n  <article>Product Page</article>\n  <article>Photo Gallery</article>\n  <article>Final Project</article>\n</div>",
        'css' => "body {\n  padding: 25px;\n  font-family: Arial, sans-serif;\n}\n.gallery {\n  display: grid;\n  grid-template-columns: repeat(3, 1fr);\n  gap: 14px;\n}\narticle {\n  min-height: 90px;\n  padding: 18px;\n  border-radius: 10px;\n  background: #5C6AC4;\n  color: white;\n}
",
    ],
    14 => [
        'html' => "<header><h1>Campus News</h1></header>\n<main>\n  <article><h2>School Fair</h2><p>Students will present creative projects this Friday.</p></article>\n  <aside><h2>Quick Links</h2><p>Events<br>Clubs<br>Contact</p></aside>\n</main>",
        'css' => "body {\n  margin: 0;\n  font-family: Arial, sans-serif;\n}\nheader {\n  padding: 20px;\n  background: #5C6AC4;\n  color: white;\n}\nmain {\n  display: grid;\n  grid-template-columns: 2fr 1fr;\n  gap: 20px;\n  padding: 25px;\n}\narticle, aside {\n  padding: 18px;\n  background: #eef0ff;\n}\n@media (max-width: 600px) {\n  main { grid-template-columns: 1fr; }\n}
",
    ],
    15 => [
        'html' => "<header>\n  <nav><strong>Bright Future</strong><a href=\"#projects\">Projects</a></nav>\n  <div class=\"hero\"><h1>Learn. Create. Share.</h1><p>A student website built with HTML and CSS.</p></div>\n</header>\n<main id=\"projects\">\n  <h2>My Projects</h2>\n  <div class=\"grid\"><article>Profile Page</article><article>School Page</article><article>Form Project</article></div>\n</main>\n<footer>Made with care by a student.</footer>",
        'css' => "* { box-sizing: border-box; }\nbody {\n  margin: 0;\n  font-family: Arial, sans-serif;\n  color: #202442;\n}\nheader {\n  padding: 24px;\n  background: #312e81;\n  color: white;\n}\nnav {\n  display: flex;\n  justify-content: space-between;\n}\nnav a { color: white; }\n.hero { padding: 45px 0; }\nmain { padding: 30px 24px; }\n.grid {\n  display: grid;\n  grid-template-columns: repeat(3, 1fr);\n  gap: 15px;\n}\narticle {\n  padding: 28px 18px;\n  background: #e0e7ff;\n  border-radius: 10px;\n}\nfooter {\n  padding: 20px 24px;\n  background: #eef0ff;\n}\n@media (max-width: 600px) {\n  .grid { grid-template-columns: 1fr; }\n}
",
    ],
];
