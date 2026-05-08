#!/usr/bin/env python3
"""Assemble OneChat briefing fragments into index.html

Fragment to screen ID mapping:
  _s0.html id="screen0" (STEP 1 ad)     -> screen0 (keep)
  _s4.html id="screen1" (STEP 2 diag)   -> screen1 (keep)
  _s1.html id="screen1" (STEP 3 trust)  -> screen2 (remap)
  _s2.html id="screen2" (STEP 4 book)   -> screen3 (remap)
  _s3.html id="screen3" (STEP 5 post)   -> screen4 (remap)
  _s5.html id="screen5" (STEP 6 after)  -> screen5 (keep)
  _s6.html id="screen6" (STEP 7 dash)   -> screen6 (keep)
  _s7.html id="screen7" (STEP 8 noshow) -> screen7 (keep)
  _s8.html id="screen8" (STEP 9 sum)    -> screen8 (keep)
"""

order = [
    ("_s0.html", None, None),
    ("_s4.html", None, None),
    ("_s1.html", "screen1", "screen2"),
    ("_s2.html", "screen2", "screen3"),
    ("_s3.html", "screen3", "screen4"),
    ("_s5.html", None, None),
    ("_s6.html", None, None),
    ("_s7.html", None, None),
    ("_s8.html", None, None),
]

footer = '\n'.join([
    '  <div class="bottom-nav">',
    '    <button class="nav-btn" id="prevBtn" onclick="prevScreen()">\u25c0 \uc774\uc804</button>',
    '    <span id="stepIndicator">1 / 9</span>',
    '    <button class="nav-btn primary" id="nextBtn" onclick="nextScreen()">\ub2e4\uc74c \u25b6</button>',
    '  </div>',
    '</div>',
    '<script src="js/app.js"></script>',
    '</body>',
    '</html>',
])

with open("_h.html", "r", encoding="utf-8") as f:
    header = f.read()

parts = []
for filename, old_id, new_id in order:
    with open(filename, "r", encoding="utf-8") as f:
        content = f.read()
    if old_id and new_id:
        content = content.replace('id="' + old_id + '"', 'id="' + new_id + '"')
    parts.append(content)

body = "\n".join(parts)
full = header + body + footer

with open("index.html", "w", encoding="utf-8") as f:
    f.write(full)

print("index.html:", len(full), "bytes - done")
