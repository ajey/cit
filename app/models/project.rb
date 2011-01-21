# encoding: UTF-8
# A logical grouping of milestones and tasks, belonging to a Customer / Client

class Project < ActiveRecord::Base
  belongs_to    :company
  belongs_to    :customer
  belongs_to    :owner, :class_name => "User", :foreign_key => "user_id"

  has_many      :users, :through => :project_permissions
  has_many      :project_permissions, :dependent => :destroy
  has_many      :pages, :as => :notable, :class_name => "Page", :order => "id desc", :dependent => :destroy
  has_many      :tasks
  has_many      :sheets
  has_many      :work_logs, :dependent => :destroy
  has_many      :project_files, :dependent => :destroy
  has_many      :project_folders, :dependent => :destroy
  has_many      :milestones, :dependent => :destroy, :order => "due_at asc, lower(name) asc"
  has_many      :forums, :dependent => :destroy

  scope :completed, where("projects.completed_at is not NULL")
  scope :in_progress, where("projects.completed_at is NULL")

  validates_length_of           :name,  :maximum=>200
  validates_presence_of         :name
  validates_presence_of         :customer_id
  after_create { |r|
    if r.create_forum && r.company.show_forum
      f = Forum.new
      f.company_id = r.company_id
      f.project_id = r.id
      f.name = r.full_name
      f.save
    end
  }

  def full_name
    customer = Customer.find(customer_id)
    return "#{customer.name} / #{name}"
  end

  def to_s
    name
  end

  def to_css_name
    "#{self.name.underscore.dasherize.gsub(/[ \."',]/,'-')} #{self.customer.name.underscore.dasherize.gsub(/[ \.'",]/,'-')}"
  end

  def total_estimate
    tasks.sum(:duration).to_i
  end

  def work_done
    tasks.sum(:worked_minutes).to_i
  end

  def overtime
    tasks.where("worked_minutes > duration").sum('worked_minutes - duration').to_i
  end

  def total_tasks_count
    if self.total_tasks.nil?
       self.total_tasks = tasks.count
       self.save
    end
    total_tasks
  end

  def open_tasks_count
    if self.open_tasks.nil?
       self.open_tasks = tasks.where("completed_at IS NULL").count
       self.save
    end
    open_tasks
  end

  def total_milestones_count
    if self.total_milestones.nil?
       self.total_milestones = milestones.count
       self.save
    end
    total_milestones
  end

  def open_milestones_count
    if self.open_milestones.nil?
       self.open_milestones = milestones.where("completed_at IS NULL").count
       self.save
    end
    open_milestones
  end

  ###
  # Updates the critical, normal and low counts for this project.
  # Also updates open and total tasks.
  ###
  def update_project_stats
    self.critical_count = tasks.where("task_property_values.property_value_id" => company.critical_values).includes(:task_property_values).count
    self.normal_count = tasks.where("task_property_values.property_value_id" => company.normal_values).includes(:task_property_values).count
    self.low_count = tasks.where("task_property_values.property_value_id" => company.low_values).includes(:task_property_values).count

    self.open_tasks = nil
    self.total_tasks = nil
  end


end


# == Schema Information
#
# Table name: projects
#
#  id               :integer(4)      not null, primary key
#  name             :string(200)     default(""), not null
#  user_id          :integer(4)      default(0), not null
#  company_id       :integer(4)      default(0), not null
#  customer_id      :integer(4)      default(0), not null
#  created_at       :datetime
#  updated_at       :datetime
#  completed_at     :datetime
#  critical_count   :integer(4)      default(0)
#  normal_count     :integer(4)      default(0)
#  low_count        :integer(4)      default(0)
#  description      :text
#  create_forum     :boolean(1)      default(TRUE)
#  open_tasks       :integer(4)
#  total_tasks      :integer(4)
#  total_milestones :integer(4)
#  open_milestones  :integer(4)
#
# Indexes
#
#  projects_company_id_index   (company_id)
#  projects_customer_id_index  (customer_id)
#  fk_projects_user_id         (user_id)
#

