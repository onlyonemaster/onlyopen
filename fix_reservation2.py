#!/usr/bin/env python3
"""Fix missing page-hero closing div in mypage_reservation_list.php."""
import re

FILE = "/disk/daily/home/kiam/mypage_reservation_list.php"

with open(FILE, "r", encoding="utf-8") as f:
    content = f.read()

# Fix: add missing </div> to close page-hero
# The pattern is: "    </div>\n\n\t\t\t\t<div class=\"m_body\">"
# "    </div>" closes page-hero-inner, but page-hero is never closed
old_pattern = '    </div>\n\n\t\t\t\t<div class="m_body">'
new_pattern = '    </div>\n</div>\n\n\t\t\t\t<div class="m_body">'

if old_pattern in content:
    content = content.replace(old_pattern, new_pattern, 1)
    print("Fixed: Added closing </div> for page-hero")
else:
    print("Pattern not found, trying alternative...")
    # Try with different whitespace
    # Search for the exact location
    idx = content.find('page-hero-inner')
    if idx > 0:
        # Find the closing </div> after page-hero-inner
        close_idx = content.find('</div>', idx + 100)
        if close_idx > 0:
            # Now find the m_body after it
            mbody_idx = content.find('class="m_body"', close_idx)
            if mbody_idx > 0:
                # Check if there's already a </div> between close_idx and mbody_idx
                between = content[close_idx+6:mbody_idx]
                print(f"Between close </div> and m_body: {repr(between[:60])}")
                if '</div>' not in between:
                    # Insert </div>\n after the close_idx + 6
                    content = content[:close_idx+6] + '\n</div>' + content[close_idx+6:]
                    print("Fixed (alternative method): Added closing </div> for page-hero")
                else:
                    print("Already has closing </div>")
            else:
                print("Could not find m_body")
        else:
            print("Could not find close </div> after page-hero-inner")
    else:
        print("Could not find page-hero-inner")

with open(FILE, "w", encoding="utf-8") as f:
    f.write(content)

print("Done!")
print(f"File size: {len(content)} bytes")