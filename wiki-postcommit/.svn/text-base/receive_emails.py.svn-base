#!/usr/bin/python
import codecs
import email
import sys
import time
import email_generator
import urllib

WAIT_TIME=10 # FIXME: Should be 10

def chapter_name2url(name):
    cleaned = urllib.quote(name.replace(' ', '_'))
    return 'http://wiki.freeculture.org/Special:URIResolver/' + cleaned

msg = email.message_from_file(sys.stdin)
chapter_name = msg['X-New-Chapter-Registration']

# We need to sleep for, oh, 10 seconds to avoid racing with SMW
time.sleep(WAIT_TIME)

# Now generate the content of a message to send
content = email_generator.go(chapter_name2url(chapter_name))

# Now email it out
email_generator.mail_freedom(subject='New registration: %s' % chapter_name,
                             message=content)
