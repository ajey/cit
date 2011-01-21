
@user = User.new
@company = Company.new

@user.name = "kumar"
@user.username = "dadaso"
@user.password = "test123"
@user.email = "kumar.better@gmail.com"
@user.time_zone = "Europe/Oslo"
@user.locale = "en_US"
@user.option_externalclients = 1
@user.option_tracktime = 1
@user.option_tooltips = 1
@user.date_format = "%d/%m/%Y"
@user.time_format = "%H:%M"
@user.admin = 1

puts "  Creating initial company..."

@company.name = "better"
@company.contact_email = "kumar.better@pmail.com"
@company.contact_name = "abc"
@company.subdomain = "better"

if @company.save
  @customer = Customer.new
  @customer.name = @company.name

  @company.customers << @customer
  puts "  Creating initial user..."
  @user.customer=@customer
  @company.users << @user
else
  c = Company.find_by_subdomain(subdomain)
  if c
    puts "** Unable to create initial company, #{subdomain} already registered.. **"

    del = "\n"
    print "Delete existing company '#{c.name}' with subdomain '#{subdomain}' and try again? [y]: "
    del = gets
    del = "y" if del == "\n"
    del.strip!
    if del.downcase.include?('y')
      c.destroy
      if @company.save
        @customer = Customer.new
        @customer.name = @company.name

        @company.customers << @customer
        puts "  Creating initial user..."
        @company.users << @user

      else
        puts " Still unable to create initial company. Check database settings..."
        exit
      end
    end

  else
    exit
  end
end

puts "Running any pending migrations..."
system("rake db:migrate RAILS_ENV=production")
puts "Done"

puts
puts "All done!"
puts "---------"

puts
puts "Make sure passenger and apache httpd are properly set up and a virtual host defined."
puts
puts "Access your installation from http://#{subdomain}.#{domain}:3000"

