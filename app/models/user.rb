# encoding: UTF-8
# A user from a company
require 'digest/md5'

class User < ActiveRecord::Base
  has_many(:custom_attribute_values, :as => :attributable, :dependent => :destroy,
           # set validate = false because validate method is over-ridden and does that for us
           :validate => false)
  include CustomAttributeMethods

  belongs_to    :company
  belongs_to    :customer
  belongs_to    :access_level
  has_many      :projects, :through => :project_permissions, :source=>:project, :conditions => ['projects.completed_at IS NULL'], :order => "projects.customer_id, projects.name", :readonly => false
  has_many      :completed_projects, :through => :project_permissions, :conditions => ['projects.completed_at IS NOT NULL'], :source => :project, :order => "projects.customer_id, projects.name", :readonly => false
  has_many      :all_projects, :through => :project_permissions, :order => "projects.customer_id, projects.name", :source => :project, :readonly => false
  has_many      :project_permissions, :dependent => :destroy

  has_many      :pages, :dependent => :nullify
  has_many      :notes, :as => :notable, :class_name => "Page", :order => "id desc"

  has_many      :tasks, :through => :task_owners
  has_many      :task_owners, :dependent => :destroy
  has_many      :work_logs
  has_many      :work_log_notifications, :dependent => :destroy

  has_many      :notifications, :class_name=>"TaskWatcher", :dependent => :destroy
  has_many      :notifies, :through => :notifications, :source => :task

  has_many      :forums, :through => :moderatorships, :order => 'forums.name'
  has_many      :moderatorships, :dependent => :destroy

  has_many      :posts
  has_many      :topics

  has_many      :monitorships, :dependent => :destroy
  has_many      :monitored_topics, :through => :monitorships, :source => 'topic', :conditions => ['monitorships.active = ? AND monitorship_type = ?', true, 'topic'], :order => 'topics.replied_at desc'
  has_many      :monitored_forums, :through => :monitorships, :source => 'forum', :conditions => ['monitorships.active = ? AND monitorship_type = ?', true, 'forum'], :order => 'forums.position'

  has_many      :moderatorships, :dependent => :destroy
  has_many      :forums, :through => :moderatorships, :order => 'forums.name'

  has_many      :widgets, :order => "widgets.column, widgets.position", :dependent => :destroy

  has_many      :task_filters, :dependent => :destroy
  has_many      :sheets, :dependent => :destroy

  has_many      :preferences, :as => :preferencable
  has_many      :received_from_emails, :class_name=>"Email", :dependent=>:destroy
  has_many      :email_addresses, :dependent=>:destroy, :order => "email_addresses.default DESC"
  has_many      :shoutboxes, :dependent=>:destroy, :order => "created_at DESC"
  
  attr_accessor :password

  has_attached_file :avatar, :whiny => false , :styles=>{ :small=> "25x25>", :large=>"50x50>"}, :path => File.join(Rails.root.to_s, 'store', 'avatars')+ "/:id_:basename_:style.:extension"

  include PreferenceMethods

  validates_length_of           :name,  :maximum=>200, :allow_nil => true
  validates_presence_of         :name

  validates_length_of           :username,  :maximum=>200, :allow_nil => true
  validates_presence_of         :username
  validates_uniqueness_of       :username, :scope => "company_id"

  validates_presence_of     :password,                   :if => :password_required?
  validates_presence_of     :password_confirmation,      :if => :password_required?
  validates_length_of       :password, :within => 6..40, :if => :password_required?
  validates_confirmation_of :password,                   :if => :password_required?

  validates_presence_of         :company
  validates_presence_of :time_format
  validates_presence_of :date_format
  validate :validate_custom_attributes

  before_create                 :generate_uuid
  after_create      :generate_widgets
  before_validation :set_date_time_formats, :on => :create
  before_destroy :reject_destroy_if_exist
  before_save :encrypt_password

  ACCESS_CONTROL_ATTRIBUTES=[:create_projects, :use_resources, :read_clients, :create_clients, :edit_clients, :can_approve_work_logs]
  attr_protected :uuid, :autologin, :admin, :company_id, ACCESS_CONTROL_ATTRIBUTES

  scope :auto_add, where(:auto_add_to_customer_tasks => true)
  scope :by_email, lambda{ |email|
    where('email_addresses.email' => email, 'email_addresses.default' => true).joins(:email_addresses).readonly(false)
  }
  ###
  # Searches the users for company and returns
  # any that have names or ids that match at least one of
  # the given strings
  ###
  def self.search(company, strings)
    conds = Search.search_conditions_for(strings, [ :name ], :start_search_only => true)
    return company.users.where(conds)
  end
  def set_access_control_attributes(params)
    ACCESS_CONTROL_ATTRIBUTES.each do |attr|
      next if params[attr].nil?
      self.attributes[:attr]=attr
    end
  end
  def avatar_path
    avatar.path(:small)
  end

  def avatar_large_path
    avatar.path(:large)
  end

  def avatar?
    !self.avatar_path.nil? and File.exist?(self.avatar_path)
  end

  def generate_uuid
    if uuid.nil?
      self.uuid = Digest::MD5.hexdigest( rand(100000000).to_s + Time.now.to_s)
    end
    if autologin.nil?
      self.autologin = Digest::MD5.hexdigest( rand(100000000).to_s + Time.now.to_s)
    end
  end

  def new_widget
    Widget.new(:user => self, :company_id => self.company_id, :collapsed => 0, :configured => true)
  end

  def generate_widgets

    old_lang = Localization.lang

    Localization.lang(self.locale || 'en_US')

    w = new_widget
    w.name =  _("Top Tasks")
    w.widget_type = 0
    w.number = 5
    w.mine = true
    w.order_by = "priority"
    w.column = 0
    w.position = 0
    w.save

    w = new_widget
    w.name = _("Newest Tasks")
    w.widget_type = 0
    w.number = 5
    w.mine = false
    w.order_by = "date"
    w.column = 0
    w.position = 1
    w.save

    w = new_widget
    w.name = _("Open Tasks")
    w.widget_type = 3
    w.number = 7
    w.mine = true
    w.column = 1
    w.position = 0
    w.save

    w = new_widget
    w.name = _("Projects")
    w.widget_type = 1
    w.number = 0
    w.column = 1
    w.position = 1
    w.save

    Localization.lang(old_lang)

  end

  def avatar_url(size=32, secure = false)
    if avatar?
      if size > 25 && File.exist?(avatar_large_path)
        '/users/avatar/'+id.to_s+'?large=1'
      else
        '/users/avatar/'+id.to_s
      end
    elsif email
      if secure
  "https://secure.gravatar.com/avatar.php?gravatar_id=#{Digest::MD5.hexdigest(self.email.downcase)}&rating=PG&size=#{size}"
      else
  "http://www.gravatar.com/avatar.php?gravatar_id=#{Digest::MD5.hexdigest(self.email.downcase)}&rating=PG&size=#{size}"
      end
    end
  end

  def display_name
    self.name
  end

  def display_login
    name + " / " + (customer.nil? ? company.name : customer.name)
  end

  def login(company = nil)
    return if !company or !company.respond_to?(:users)
    #user = company.users.where(:active=>true, :username => self.username, :password => self.password).first
    user = User.authenticate(self.username,self.password)
    unless user.nil?
      user.last_login_at =Time.now.utc
      user.save
    end
    return user
  end

  def can?(project, perm)
    return true if project.nil?

    @perm_cache ||= {}
    unless @perm_cache[project.id]
      @perm_cache[project.id] ||= {}
      self.project_permissions.each do | p |
        @perm_cache[p.project_id] ||= {}
        ProjectPermission.permissions.each do |p_perm|
          @perm_cache[p.project_id][p_perm] = p.can?(p_perm)
        end
      end
    end

    (@perm_cache[project.id][perm] || false)
  end

  def can_all?(projects, perm)
    projects.each do |p|
      return false unless self.can?(p, perm)
    end
    true
  end

  def can_any?(project, perm)
    projects.each do |p|
      return true if self.can?(p, perm)
    end
    false
  end

  def admin?
    self.admin > 0
  end

  ###
  # Returns true if this user is allowed to view the clients section
  # of the website.
  ###
  def can_view_clients?
    self.admin? or
      (self.read_clients? and self.option_externalclients?)
  end

  # Returns true if this user is allowed to view the given task.
  def can_view_task?(task)
    ! Task.accessed_by(self).find_by_id(task).nil?
  end

  # Returns a fragment of sql to restrict tasks to only the ones this
  # user can see
  def user_tasks_sql
    res = []
    if self.projects.any?
      res << "tasks.project_id in (#{ all_project_ids.join(",") })"
    end

    res << "task_users.user_id = #{ self.id }"

    res = res.join(" or ")
    return "(#{ res })"
  end

  # Returns an array of all project ids that this user has
  # access to. Even completed projects will be included.
  def all_project_ids
    @all_project_ids ||= all_projects.map { |p| p.id }
  end

  # Returns an array of all customers this user has access to
  # (through projects).
  # If options is passed, those options will be passed to the find.
  def customers(options = {})
    opts = search_options_through_projects("customers", options)
    return company.customers.where(opts[:conditions]).includes(opts[:include]).order(opts[:order]).limit(opts[:limit]).joins(opts[:joins]).offset(opts[:offset])
  end

 # Returns an array of all milestone this user has access to
  # (through projects).
  # If options is passed, those options will be passed to the find.
  def milestones(options = {})
    opts = search_options_through_projects("milestones", options)
    company.milestones.where(opts[:conditions]).includes(opts[:include]).order(opts[:order]).limit(opts[:limit]).joins(opts[:joins]).offset(opts[:offset])
  end

  def moderator_of?(forum)
    moderatorships.where('forum_id = ?', (forum.is_a?(Forum) ? forum.id : forum)).count == 1
  end

  def tz
    unless @tz
      @tz = TZInfo::Timezone.get(self.time_zone)
    end
    @tz
  end

  # Get date formatter in a form suitable for jQuery-UI
  def dateFormat
    return 'mm/dd/yy' if self.date_format == '%m/%d/%Y'
    return 'dd/mm/yy' if self.date_format == '%d/%m/%Y'
    return 'yy/mm/dd' if self.date_format == '%Y-%m-%d'
  end

  def to_s
    str = [ name ]
    str << "(#{ customer.name })" if customer

    str.join(" ")
  end

  # This is used for the json formatting used for autocomplete
  def value
    return name
  end

  # This is used for the json formatting used for autocomplete
  def label
    return name
  end

  # Returns an array of all task filters this user can see
  def visible_task_filters
    if @visible_task_filters.nil?
      @visible_task_filters = (task_filters.visible + company.task_filters.shared.visible).uniq
      @visible_task_filters = @visible_task_filters.sort_by { |tf| tf.name.downcase.strip }
    end

    return @visible_task_filters
  end

  def project_ids_for_sql
    unless @current_project_ids
      @current_project_ids=self.project_ids
      @current_project_ids=@current_project_ids.empty? ? "0" : @current_project_ids.join(",")
    end
    @current_project_ids
  end

  def email
    email_addresses.detect { |pv| pv.default }.try(:email)
  end

  alias_method :primary_email, :email

  def email=(new_email)
    if new_record? || email_addresses.size == 0 || email_addresses.detect{|pv| pv.default }.blank?
      email_addresses.build(:email => new_email, :default => true)
    else
      email_addresses.detect{ |pv| pv.default }.attributes= {:email => new_email}
    end
  end

  # Authenticates a user by their login name and unencrypted password.  Returns the user or nil.
  def self.authenticate(login, password)
    u = User.find(:first, :conditions =>["active= ? and username = ?",true, login])
    u && u.authenticated?(password) ? u : nil
  end

  # Encrypts some data with the salt.
  def self.encrypt(password, salt)
    Digest::SHA1.hexdigest("--#{salt}--#{password}--")
  end

 
  # Encrypts the password with the user salt
  def encrypt(password)
    self.class.encrypt(password, salt)
  end

  def authenticated?(password)
    crypted_password == encrypt(password)
  end

  def remember_token?
    remember_token_expires_at && Time.now.utc < remember_token_expires_at
  end

  # These create and unset the fields required for remembering users between browser closes
  def remember_me
    self.remember_token_expires_at = 2.weeks.from_now.utc
    self.remember_token            = encrypt("#{email}--#{remember_token_expires_at}")
    save(false)
  end

  def forget_me
    self.remember_token_expires_at = nil
    self.remember_token            = nil
    save(false)
  end

  # before filter
  def encrypt_password
    return if password.blank?
    self.salt = Digest::SHA1.hexdigest("--#{Time.now.to_s}--#{email}--") if new_record?
    self.crypted_password = encrypt(password)
  end

  def password_required?
    crypted_password.blank? || !password.blank?
  end

private
  def reject_destroy_if_exist
    [:work_logs, :topics, :posts].each do |association|
      errors.add_to_base("The user has the #{association.to_s.humanize}, please remove them first or deactivate user.") unless eval("#{association}.count").zero?
    end
    if errors.count.zero?
      ActiveRecord::Base.connection.execute("UPDATE tasks set creator_id = NULL WHERE company_id = #{self.company_id} AND creator_id = #{self.id}")
      return true
    else
      return false
    end
  end

  # Sets up search options to use in a find for things linked to
  # through projects.
  # See methods customers and milestones.
  def search_options_through_projects(lookup, options = {})
    conditions = []
    conditions << User.send(:sanitize_sql_for_conditions, options[:conditions])
    conditions << User.send(:sanitize_sql_for_conditions, [ "projects.id in (?)", all_project_ids ])
    conditions = conditions.compact.map { |c| "(#{ c })" }
    options[:conditions] = conditions.join(" and ")

    options[:include] ||= []
    options[:include] << (lookup == "milestones" ? :project : :projects)

    options = options.merge(:order => "lower(#{ lookup }.name)")

    return options
  end

  # Sets the date time format for this user to a sensible default
  # if it hasn't already been set
  def set_date_time_formats
    first_user = company.users.detect { |u| u != self }

    if first_user and first_user.time_format and first_user.date_format
      self.time_format = first_user.time_format
      self.date_format = first_user.date_format
    else
      self.date_format = "%d/%m/%Y"
      self.time_format = "%H:%M"
    end
  end


end



# == Schema Information
#
# Table name: users
#
#  id                         :integer(4)      not null, primary key
#  name                       :string(200)     default(""), not null
#  username                   :string(200)     default(""), not null
#  password                   :string(200)     default(""), not null
#  company_id                 :integer(4)      default(0), not null
#  created_at                 :datetime
#  updated_at                 :datetime
#  email                      :string(200)
#  last_login_at              :datetime
#  admin                      :integer(4)      default(0)
#  time_zone                  :string(255)
#  option_tracktime           :integer(4)
#  option_externalclients     :integer(4)
#  option_tooltips            :integer(4)
#  seen_news_id               :integer(4)      default(0)
#  last_project_id            :integer(4)
#  last_seen_at               :datetime
#  last_ping_at               :datetime
#  last_milestone_id          :integer(4)
#  last_filter                :integer(4)
#  date_format                :string(255)     not null
#  time_format                :string(255)     not null
#  send_notifications         :integer(4)      default(1)
#  receive_notifications      :integer(4)      default(1)
#  uuid                       :string(255)     not null
#  seen_welcome               :integer(4)      default(0)
#  locale                     :string(255)     default("en_US")
#  duration_format            :integer(4)      default(0)
#  workday_duration           :integer(4)      default(480)
#  posts_count                :integer(4)      default(0)
#  newsletter                 :integer(4)      default(1)
#  option_avatars             :integer(4)      default(1)
#  autologin                  :string(255)     not null
#  remember_until             :datetime
#  option_floating_chat       :boolean(1)      default(TRUE)
#  days_per_week              :integer(4)      default(5)
#  enable_sounds              :boolean(1)      default(TRUE)
#  create_projects            :boolean(1)      default(TRUE)
#  show_type_icons            :boolean(1)      default(TRUE)
#  receive_own_notifications  :boolean(1)      default(TRUE)
#  use_resources              :boolean(1)
#  customer_id                :integer(4)
#  active                     :boolean(1)      default(TRUE)
#  read_clients               :boolean(1)      default(FALSE)
#  create_clients             :boolean(1)      default(FALSE)
#  edit_clients               :boolean(1)      default(FALSE)
#  can_approve_work_logs      :boolean(1)
#  auto_add_to_customer_tasks :boolean(1)
#
# Indexes
#
#  index_users_on_username_and_company_id  (username,company_id) UNIQUE
#  users_uuid_index                        (uuid)
#  users_company_id_index                  (company_id)
#  index_users_on_last_seen_at             (last_seen_at)
#  index_users_on_autologin                (autologin)
#  index_users_on_customer_id              (customer_id)
#
