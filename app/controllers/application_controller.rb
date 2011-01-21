# encoding: UTF-8
# The filters added to this controller will be run for all controllers in the application.
# Likewise will all the methods added be available for all controllers.

require 'digest/md5'
require "#{Rails.root}/lib/misc"
require "#{Rails.root}/lib/localization"

class ApplicationController < ActionController::Base
  include Misc
  include DateAndTimeHelper  

  helper :task_filter
  helper :users
  helper :date_and_time
  helper :todos
  helper :tags
  helper :time_tracking
  helper :resources

#  helper :all

  helper_method :last_active
  helper_method :render_to_string
  helper_method :current_user
  helper_method :tz
  helper_method :current_projects
  helper_method :current_project_ids
  helper_method :completed_milestone_ids
  helper_method :worked_nice
  helper_method :link_to_task
  helper_method :current_task_filter
  helper_method :current_templates

  before_filter :authorize, :except => [ :login, :validate,
                                         :show_logo, :about, :screenshots, :terms, :policy,
                                         :unsubscribe, :igoogle_setup, :igoogle
                                       ]

#  protect_from_forgery :secret => '112141be0ba20082c17b05c78c63f357'
  def current_user
    unless @current_user
      @current_user = User.includes(:projects, { :company => :properties }).find(session[:user_id])
    end
    @current_user
  end

  def current_sheet
    unless @current_sheet
      @current_sheet = Sheet.where("user_id = ?", session[:user_id]).order('sheets.id').includes(:task).first
      unless @current_sheet.nil?
        if @current_sheet.task.nil?
          @current_sheet.destroy
          @current_sheet = nil
        end
      end
    end
    @current_sheet
  end

  def tz
    unless @tz
      @tz = TZInfo::Timezone.get(current_user.time_zone)
    end
    @tz
  end

  # Make sure the session is logged in
  def authorize
    session[:history] ||= []

    # Remember the previous _important_ page for returning to after an edit / update.
    if( request.fullpath.include?('/list') || request.fullpath.include?('/search') || request.fullpath.include?('/edit_preferences') ||
        request.fullpath.include?('/timeline') || request.fullpath.include?('/gantt') ||
        request.fullpath.include?('/forums') || request.fullpath.include?('/topics') || request.fullpath.include?('/projects') ) &&
        !request.xhr?
      session[:history] = [request.fullpath] + session[:history][0,3] if session[:history][0] != request.fullpath
    end


    logger.info("remember[#{session[:remember_until]}]")

    # We need to re-authenticate
    if session[:user_id] && session[:remember_until] && session[:remember_until] < Time.now.utc
      session[:user_id] = nil
      session[:remember_until] = nil
    end

    if session[:user_id].to_i == 0
      if !(request.fullpath.include?('/login/login') || request.xhr? || request.format.js?)
        session[:redirect] = request.fullpath
      elsif session[:history] && session[:history].size > 0
        session[:redirect] = session[:history][0]
      end

      if request.xhr?
        render :update do |page|
          page.redirect_to :controller => 'login', :action => 'login'
        end
      elsif request.format.js? && request.post?
        #return json for ajax form callback
        render :text => {:status => "session timeout"}.to_json and return
      else
        redirect_to "/login/login"
      end
    else
      session[:remember_until] = Time.now.utc + ( session[:remember].to_i == 1 ? 1.month : 1.hour )

      current_sheet

      # Set current locale
      Localization.lang(current_user.locale || 'en_US')

      if session[:redirect]
        redirect_to session[:redirect]
        session[:redirect] = nil
      end
    end
    true
  end

  # Parse <tt>1w 2d 3h 4m</tt> or <tt>1:2:3:4</tt> => minutes or seconds
  def parse_time(input, minutes = false)
    TimeParser.parse_time(current_user, input, minutes)
  end

  # List of Users current Projects ordered by customer_id and Project.name
  def current_projects
    current_user.projects
  end

  # List of current Project ids
  def current_project_ids
    current_user.project_ids
  end

  def all_projects
    current_user.all_projects
  end

  # List of completed milestone ids, joined with ,
  def completed_milestone_ids
    unless @milestone_ids
      @milestone_ids ||= Milestone.select("id").where("company_id = ? AND completed_at IS NOT NULL", current_user.company_id).collect{ |m| m.id }
      @milestone_ids = [-1] if @milestone_ids.empty?
    end
    @milestone_ids
  end

  def worked_nice(minutes)
    format_duration(minutes, current_user.duration_format, current_user.workday_duration, current_user.days_per_week)
  end

  def highlight( text, k )
    t = text.gsub(/(#{Regexp.escape(k)})/i, '<strong>\1</strong>').html_safe
  end

  def highlight_all( text, keys )
    keys.each do |k|
      text = highlight(text, k)
    end
    text
  end

#  def rescue_action(exception)
#    log_exception(exception)
#    exception.is_a?(ActiveRecord::RecordInvalid) ? render_invalid_record(exception.record) : super
#  end

  def render_invalid_record(record)
    render :action => (record.new_record? ? 'new' : 'edit')
  end

  def admin?
    current_user.admin > 0
  end

  def logged_in?
    true
  end

  def last_active
    session[:last_active] ||= Time.now.utc
  end

  ###
  # Returns the list to use for auto completes for user names.
  ###
  def auto_complete_for_user_name
    text = params[:term]
    if !text.blank?
      # the next line searches for names starting with given text OR surname (space started) starting with text
      @users = current_user.company.users.order('name').where('name LIKE ? OR name LIKE ?', text + '%', '% ' + text + '%').limit(50)
      render :json=> @users.collect{|user| {:value => user.name + ' (' + user.customer.name + ')', :id=> user.id} }.to_json
    else
      render :nothing=> true
    end
  end

  ###
  # Returns the list to use for auto completes for customer names.
  ###
  def auto_complete_for_customer_name
    text = params[:term]
    if !text.blank?
      @customers = current_user.company.customers.order('name').where('name LIKE ? OR name LIKE ?', text + '%', '% ' + text + '%').limit(50)
      render :json=> @customers.collect{|customer| {:value => customer.name, :id=> customer.id} }.to_json
    else
      render :nothing=> true
    end
  end

  ###
  # Returns the layout to use to display the current request.
  # Add a "layout" param to the request to use a different layout.
  ###
  def decide_layout
    params[:layout] || "application"
  end

  ###
  # Which company does the served hostname correspond to?
  ###
  def company_from_subdomain
    if @company.nil?
      subdomain = request.subdomains.first if request.subdomains

      @company = Company.where("subdomain = ?", subdomain).first
      if Company.count == 1
        @company ||= Company.order("id asc").first
      end
    end

    return @company
  end

  # Redirects to the last page this user was on.
  # If the current request is using ajax, uses js to do the redirect.
  # If the tutorial hasn't been completed, sends them back to that page
  def redirect_from_last
    url = "/activities/list" # default

    if session[:history] && session[:history].any?
      url = session[:history].first
    elsif !current_user.seen_welcome?
      url = "/activities/welcome"
    end

    url = url.gsub("format=js", "")
    redirect_using_js_if_needed(url)
  end

  private

  # Returns a link to the given task.
  # If highlight keys is given, that text will be highlighted in
  # the link.
  def link_to_task(task, truncate = true, highlight_keys = [])
    link = "<strong>#{task.issue_num}</strong> "
    if task.is_a? Template
      url = url_for(:id => task.task_num, :controller => 'task_templates', :action => 'edit')
    else
      url = url_for(:id => task.task_num, :controller => 'tasks', :action => 'edit')
    end
    title = task.to_tip(:duration_format => current_user.duration_format,
                        :workday_duration => current_user.workday_duration,
                        :days_per_week => current_user.days_per_week,
                        :user => current_user)
    title = highlight_all(title, highlight_keys)

    html = {
      :class => "tooltip tasklink #{task.css_classes}",
      :title => title
    }

    if @ajax_task_links
      html[:onclick] = "showTaskInPage(#{ task.task_num }); return false;"
    end

    text = truncate ? task.name : self.class.helpers.truncate(task.name, :length => 80)
    text = highlight_all(text, highlight_keys)

    link += self.class.helpers.link_to(text, url, html)
    return link.html_safe
  end

  # returns the current task filter (or a new, blank one
  # if none set)
  def current_task_filter
    @current_task_filter ||= TaskFilter.system_filter(current_user)
  end

  # Redirects to the given url. If the current request is using ajax,
  # javascript will be used to do the redirect.
  def redirect_using_js_if_needed(url)
    url = url_for(url)

    if !request.xhr?
      redirect_to url
    else
      render(:update) { |page| page << "parent.document.location = '#{ url }'" }
    end
  end
  def current_templates
    Template.where("project_id IN (?) AND company_id = ?", current_project_ids, current_user.company_id)
  end
end
