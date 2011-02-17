class CreateShoutboxes < ActiveRecord::Migration
  def self.up
    create_table :shoutboxes do |t|
      t.string  :name, :website
      t.string  :message, :length => 250
      t.integer :user_id
      t.timestamps
    end
  end

  def self.down
    drop_table :shoutboxes
  end
end
