class AddLastactivityToUsers < ActiveRecord::Migration
  def self.up
    add_column :users, :lastactivity, :integer, :default => 0
  end

  def self.down
    reomve_column :users, :lastactivity
  end
end
