require 'compass'
require 'compass/commands'
require 'json'
%w(
    sass/importers/symfony_importer
    sass/script/symfony_functions
).each do |inc|
    require File.join(File.dirname(__FILE__), inc)
end

project_root = ARGV[0]
bundle_map_file = ARGV[1]
bundle_public_map_file = ARGV[2]

# TODO: check if given bundle map file paths exist

def parse_bundle_map(file)
    return JSON.parse(File.read(file))
end

puts "Bundle map         : #{bundle_map_file}"
puts "Public bundle map  : #{bundle_public_map_file}"
puts "Project root       : #{project_root}"

puts "Bundle importer    :"
bundle_map = parse_bundle_map(bundle_map_file)
bundle_map.each_pair do |bundle_name, bundle_root|
	puts "  > #{bundle_name} => #{bundle_root}"
	Sass.load_paths << Sass::Importers::SymfonyImporter.new(bundle_root, bundle_name)
end

compiler = Compass::Commands::UpdateProject.new(project_root, {
	:sass_options => {
		:bundle_public_map => parse_bundle_map(bundle_public_map_file)
	}
})
compiler.execute()
