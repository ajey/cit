gem "rails", "3.0.3"

source 'http://rubygems.org'
gem 'daemons',           '1.1.0'
gem 'will_paginate',     '>=3.0pre2'
gem 'icalendar',         '>=1.1.5'
gem 'tzinfo'
gem 'RedCloth',          '4.2.3'
gem 'gchartrb',          '>=0.8', :require=>"google_chart"
gem 'smurf'
gem 'paperclip',         '>=2.3.3'
gem 'json'
gem  'mysql'
gem 'acts_as_tree'
gem 'acts_as_list'
gem 'dynamic_form'
gem 'remotipart'
gem "delayed_job",       '>=2.1.1'
gem "exception_notification", :git => "git://github.com/rails/exception_notification.git", :require => 'exception_notifier'

platforms :ruby_18 do
  gem 'fastercsv'
end

group :test do
  gem "shoulda",          '>=2.11.3'
  gem "rspec"
  gem "rspec-rails",      '>=2.0.0'
  gem "faker"
  gem "ZenTest"
  gem "autotest"
  gem "autotest-rails"
  gem "cucumber",         '>=0.8.5'
  gem "database_cleaner", '>=0.6.0'
  gem "cucumber-rails",   '>=0.3.2'
  gem "capybara",         '>=0.4.0'
  gem "ruby-prof"
  platforms :ruby_18 do
    gem "ruby-debug"
  end
  gem "launchy"
  
  platforms :ruby_19 do
  	gem 'test-unit'
  end
  
  gem "machinist",        '1.0.6'
  gem "ci_reporter"
end

group :development do
#  gem "bullet"
end
