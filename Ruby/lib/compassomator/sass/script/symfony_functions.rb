module Sass::Script::Functions
  def bundle_public(string)
    assert_type string, :String

    uri = string.value

    # replace the bundle reference name with the public path
    options[:bundle_public_map].each_pair do |bundle_name, public_dir|
        uri = uri.sub(/@#{bundle_name}/, public_dir)
    end

    Sass::Script::String.new(uri)
  end
  declare :bundle_public, :args => [:string]
end