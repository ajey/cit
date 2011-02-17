class ShoutboxController < ApplicationController

  def index
    @shouts = current_user.shoutboxes

    respond_to do |format|
      format.html # index.html.erb
      format.xml  { render :xml => @shouts }
    end

  end
  
  def shout
    if request.xhr?    
      sh = current_user.shoutboxes.find(:first, 
            :conditions=> ["message =? and website =?",params[:shoutbox][:message],params[:shoutbox][:website]])
      if !sh
        shout = current_user.shoutboxes.build(params[:shoutbox])
        shout.save!
        
        respond_to do | format |
          format.js 
        end
      end  
    end
  end

  def refresh

    if request.xhr?
      respond_to do | format |
        format.js 
      end  
    end
  end

end
