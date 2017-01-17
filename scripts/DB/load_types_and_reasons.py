#!/usr/bin/env python

# Script that loads "Types of appointments" and "Reasons" from CSV and inserts
# them into the database. 
# 
# It uses mysql.connector, you can download it from 
# https://dev.mysql.com/downloads/connector/python/2.1.html
# 
#
# David Campos R.
# 31/12/2016

import sys, csv

try:
	import mysql.connector
except ImportError:
	print "Couldn't find mysql.connector, you can download it from" 
	print "https://dev.mysql.com/downloads/connector/python/2.1.html"
	sys.exit(0)

dbhost = raw_input("Database host: ")
dbuser = raw_input("Database user: ")
dbpassword = raw_input("Database password: ")
dbdatabase = raw_input("Database: ")
reasons_file = raw_input("Reasons file: ")
appointments_file = raw_input("Appointments file: ")

try:
	print "Connecting..."
	cnx = mysql.connector.connect(user=dbuser,
			password=dbpassword, host=dbhost, database=dbdatabase)
	print "Connected."
	cursor = cnx.cursor();
	
	add_reason = ("INSERT INTO `Reasons` "
	              "(`name`, `description`) "
	              "VALUES (%s, %s)")
	
	add_appoin = ("INSERT INTO `Appointments` "
	              "(`name`, `description`, `icon_id`) "
	              "VALUES (%s, %s, %d)")
	
	print "Inserting reasons..."
	#Open the csv, read and insert
	with open(reasons_file, 'rb') as csvfile:
		csvreader = csv.reader(csvfile, delimiter=';', quotechar='|')
		for row in csvreader:
			cursor.execute(add_reason, row)
	
	print "Inserting appointments..."
	with open(appointments_file, 'rb') as csvfile:
		csvreader = csv.reader(csvfile, delimiter=';', quotechar='|')
		for row in csvreader:
			cursor.execute(add_appoin, row)
	
	cnx.commit()
	
	cursor.close()
	print "Finished."
except mysql.connector.Error as err:
  if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
    print("Something is wrong with your user name or password")
  elif err.errno == errorcode.ER_BAD_DB_ERROR:
    print("Database does not exist")
  else:
    print(err)
else:
  cnx.close()
