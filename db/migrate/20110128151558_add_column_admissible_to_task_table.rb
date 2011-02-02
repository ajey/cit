class AddColumnAdmissibleToTaskTable < ActiveRecord::Migration
  def self.up
    add_column :tasks, :admissible, :boolean
  end

  def self.down
   remove_column :tasks, :admissible
  end
end
