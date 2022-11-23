#! /usr/bin/env python
# -*- coding: utf-8 -*-

#
# This file is part of PLACEMENT software
# PLACEMENT helps users to bind their processes to one or more cpu-cores
#
# PLACEMENT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your options) any later version.
#
#  Copyright (C) 2015-2018 Emmanuel Courcelle
#  PLACEMENT is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with PLACEMENT.  If not, see <http://www.gnu.org/licenses/>.
#
#  Authors:
#        Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
#

# Testing the login functionality (only the DBG one)

from selenium import webdriver
from selenium.webdriver.common.by import By

from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager

from connexion_dbg import *
from objecttest import *

class testLogin(ObjectTest):

    def __init__(self,driver):
        # Initializing the ObjectPage classe
        self.__connexion_dbg = Connexion_dbg(driver)

    def testOk(self, v):
        self.__connexion_dbg.connect('Admin Admin')


if __name__ == '__main__':
    driver = webdriver.Chrome(service=ChromeService(executable_path=ChromeDriverManager().install()))
    t = testLogin(driver)
    t.testOk('Admin Admin')
    t.testKo('Bob admin')
    print ("Test OK")
    driver.quit()
