# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|

  #---Puppet Lab's CentOS Boxes---
  #https://github.com/puppetlabs/puppet-vagrant-boxes

  #Vanilla
  config.vm.box = "centos-65-x64-virtualbox-nocm"
  config.vm.box_url = "http://puppet-vagrant-boxes.puppetlabs.com/centos-65-x64-virtualbox-nocm.box"

  #Puppet
  #config.vm.box = "centos-65-x64-virtualbox-puppet"
  #config.vm.box_url = "http://puppet-vagrant-boxes.puppetlabs.com/centos-65-x64-virtualbox-puppet.box"

  #---Networking---

  # Port forward 80 to 8080
  config.vm.network :forwarded_port, guest: 80, host: 8080, auto_correct: true
  #config.vm.network :forwarded_port, guest: 443, host: 443, auto_correct: true
  config.vm.network :forwarded_port, guest: 3306, host: 3306, auto_correct: true

  #Uncomment this if you want bridged network functionality
  #config.vm.network :public_network

  #Install lamp and so on
  #In future will probably swap this out with something like Puppet
  config.vm.provision :shell, :path => "environment/scripts/iptables.sh"
  config.vm.provision :shell, :path => "environment/scripts/php.sh"
  config.vm.provision :shell, :path => "environment/scripts/apache.sh"
  #config.vm.provision :shell, :path => "environment/scripts/custom-yum-remi-repo.sh" # needed for xsendfile
  config.vm.provision :shell, :path => "environment/scripts/mysql.sh"
  config.vm.provision :shell, :path => "environment/scripts/composer.sh"
  config.vm.provision :shell, :path => "environment/scripts/bootstrap.sh"
  config.vm.provision :shell, :path => "environment/scripts/always.sh", run: "always"

end
