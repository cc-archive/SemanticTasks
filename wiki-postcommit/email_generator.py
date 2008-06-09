import RDF
import urllib
import StringIO
label = RDF.Uri('http://www.w3.org/2000/01/rdf-schema#label')

#RDF.debug(1)

def totally_lame_whatever(parser, uri):
    if 'URIResolver' in str(uri):
        uri_str = str(uri)
        uri_str = uri_str.replace('URIResolver', 'ExportRDF')
        uri_str = uri_str.replace('-3A', ':') # WTF, honestly?
        uri = RDF.Uri(uri_str)
    return parser.parse_as_stream(uri)

def property2label(property, model):
    qs = RDF.Statement(property.uri, label, None)
    results = model.find_statements(qs)
    for result in results:
        return str(result.object)
    return '?? UNKNOWN PROPERTY ??'

def properties_table(uri, model):
    qs = RDF.Statement(uri, None, None)
    results = model.find_statements(qs) # Everything we know about uri
    known_properties = []
    for result in results:
        known_properties.append( (property2label(result.predicate, model), format_property_value(result.object)) )
    return sorted([p for p in known_properties if '?? UNKNOWN' not in p[0]])

def format_property_value(value):
    if value.type == 1: # WTF - is there a list of these constants somewhere?
        return str(value.uri)
    if value.type == 2: # String
        return value.literal_value['string']
    return (value, value.type, str(value))

def properties_tables(uri, model):
    base_table = properties_table(uri, model)
    extra_tables = {}
    for (prop, value) in base_table:
        if 'URIResolver' in value:
            # Parse the URI...
            uri = RDF.Uri(value)
            parser = RDF.Parser('raptor')
            for statement in totally_lame_whatever(parser, uri):
                model.add_statement(statement)
            # ...and stash its data
            extra_tables[value] = properties_table(uri, model)
    return (base_table, extra_tables)

def init_model(get_me, query_uri):
    ''' Input: An RDF.Uri() to start from.
    Output: A model with that sucker parsed. '''
    storage=RDF.Storage(storage_name="hashes",
                    name="test",
                    options_string="new='yes',hash-type='memory',dir='.'")
    if storage is None:
      raise "new RDF.Storage failed"

    model=RDF.Model(storage)
    if model is None:
      raise "new RDF.model failed"

    parser = RDF.Parser('raptor')
    for statement in totally_lame_whatever(parser, get_me):
        model.add_statement(statement)
    return model

def go(url_string):
    uri = RDF.Uri(url_string)
    get_me = RDF.Uri(url_string.replace('URIResolver', 'ExportRDF')) # THIS SUCKS
    model = init_model(get_me, uri)
    table, extra_tables = properties_tables(uri, model)
    return format_tables(uri, table, extra_tables)

def main(filename):
    s = open(filename).read().strip()
    print go('http://wiki.freeculture.org/Special:URIResolver/' + urllib.quote(s.replace(' ', '_')))

def format_table(table):
    # calculate width
    # let's say max plus four
    if not table:
        return '\n\n'
    max_label_width = max([ len(label) for (label, value) in table ])
    width = max_label_width + 4 # shrug, four seems fine.

    io = StringIO.StringIO()

    for label, value in table:
        print >> io, label.ljust(width) + ':', value
    
    return io.getvalue()

def format_tables(uri, table, extra_tables):
    io = StringIO.StringIO()

    print >> io, "Information about <%s>:" % str(uri)
    print >> io, ''

    print >> io, format_table(table)
    print >> io, ''

    if extra_tables:
        print >> io, "Information about other linked resources:"
        print >> io, ''

        for uri in extra_tables:
            print >> io, format_tables(uri, extra_tables[uri], {})

    return io.getvalue()

import email
import email.MIMEText
import smtplib
import BeautifulSoup
def mail_freedom(subject, message, from_addr='freecult@freeculture.org',
                 to_addr='freeculture@asheesh.org'):
    assert type(message) == type(u'nicode')
    msg = email.MIMEText.MIMEText(message.encode('utf-8'), _charset='utf-8')
    msg['Subject'] = subject
    msg['From'] = from_addr
    msg['To'] = to_addr

    # Then talk SMTP
    smtp = smtplib.SMTP()
    smtp.connect()
    smtp.sendmail(from_addr=from_addr, to_addrs=[to_addr], msg=msg.as_string())
    smtp.close()

import web

urls = (
    '/(.*)', 'hello'
)

class hello:        
    def GET(self, name):
            print go('http://wiki.freeculture.org/Special:URIResolver/' + urllib.quote(name.replace(' ', '_')))

if __name__ == "__main__": web.run(urls, globals())    
