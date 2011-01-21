class AddColumnsSaltAndCryptedPasswordToUsersTable < ActiveRecord::Migration
  def self.up
    add_column  :users, :salt, :string
    add_column  :users, :crypted_password, :string
    rename_column :users, :password, :pass
  end

  def self.down
   remove_column :users, :salt
   remove_column :users, :crypted_password
    rename_column :users, :pass, :password  
  end
end
