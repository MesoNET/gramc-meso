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

# Testing the connexion_dbg url

from lib.objectpage import *
from selenium.webdriver.common.by import By
from selenium.webdriver.support.select import Select

class Connexion_dbg(ObjectPage):

    def __init__(self,d):
        super(Connexion_dbg,self).__init__(d)


    def connect(self, userName):
        d = self._getDriver()
        b = self._getBaseUrl()
      
        d.get(b + "connexion_dbg")
        h1 = d.find_element(By.TAG_NAME,"h1")
        t = d.title
        assert t.startswith("Plateforme d'attribution de ressources de MESONET")
        assert h1.text == "Veuillez vous identifier"

        select_element = d.find_element(by=By.CSS_SELECTOR, value="#form_data")
        s = Select(select_element)
        submit_button = d.find_element(by=By.CSS_SELECTOR, value="button")

        s.select_by_visible_text(userName)
        submit_button.click()

        connected = d.find_element(by=By.CSS_SELECTOR, value="div.sous_header div.profil a").text
        assert connected.startswith(userName)

        return True

